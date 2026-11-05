<?php

namespace App\Services;

use App\Helpers\FirestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Point 103 - prices that follow the traffic, for the website's Parcel and
 * Delivery prices. The rules are the admin panel's
 * docs/APP-SPEC-TRAFFIC-PRICING.md; the field names are that contract.
 *
 *   price = (distance part as today + minutes x price per minute) x multiplier
 *
 * - The minutes are the expected trip time WITH TRAFFIC, from Google's Routes
 *   API (the site's map is Google; the key is settings/googleMapKey). Parcel
 *   and Delivery are paid at checkout, so this estimate is also the final.
 *   When Google gives no time (the Routes API not enabled on the key, a
 *   timeout), there is no time part - the price is the distance part, as
 *   before - and the multiplier still applies.
 * - The multiplier is the region's (regions/{id}.trafficPricing): the higher
 *   of a busy hour (the region's local time) and a busy zone (the pickup point
 *   inside zone/{id}.area). 1 when off, or the service is not ticked.
 *
 * The pure parts (multiplier, busy hours, zones) take their data as
 * arguments, so they can be tested without Firestore or Google.
 */
class TrafficPricing
{
    public const SERVICES = ['cab', 'parcel', 'delivery'];

    /* ------------------------------------------------------------ pure -- */

    /** The region's traffic settings if they apply to this service, else null. */
    public static function settingsFor(?array $region, string $service): ?array
    {
        $tp = is_array($region['trafficPricing'] ?? null) ? $region['trafficPricing'] : null;
        if (!$tp || ($tp['enabled'] ?? false) !== true) {
            return null;
        }
        $services = is_array($tp['services'] ?? null) ? $tp['services'] : [];

        return in_array($service, $services, true) ? $tp : null;
    }

    /** The region's time zone, from its country (regions keep no zone of their
     *  own). The first zone of the country; UTC when unknown. */
    public static function timeZoneFor(?array $region): \DateTimeZone
    {
        $code = strtoupper(trim((string) ($region['countryCode'] ?? '')));
        if (strlen($code) === 2) {
            try {
                $ids = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $code);
                if (!empty($ids)) {
                    return new \DateTimeZone($ids[0]);
                }
            } catch (\Throwable $e) {
            }
        }

        return new \DateTimeZone('UTC');
    }

    /** "HH:MM" as minutes since midnight, or null. */
    public static function clock($value): ?int
    {
        if (!is_string($value) || !preg_match('/^(\d{1,2}):(\d{2})$/', trim($value), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];

        return ($h < 24 && $i < 60) ? $h * 60 + $i : null;
    }

    /** The highest busy-hour multiplier at this local time, or 1.
     *  days: 1 = Monday ... 7 = Sunday. An end before the start runs past
     *  midnight: 22:00-02:00 on Friday covers Friday 22:00 to Saturday 02:00. */
    public static function busyHourMultiplier(array $busyHours, \DateTimeInterface $local): float
    {
        $day = (int) $local->format('N');
        $previousDay = $day === 1 ? 7 : $day - 1;
        $now = (int) $local->format('G') * 60 + (int) $local->format('i');
        $best = 1.0;

        foreach ($busyHours as $row) {
            $start = self::clock($row['start'] ?? null);
            $end = self::clock($row['end'] ?? null);
            $multiplier = ParcelPricing::num($row['multiplier'] ?? null);
            $days = array_map('intval', is_array($row['days'] ?? null) ? $row['days'] : []);
            if ($start === null || $end === null || $start === $end || $multiplier === null || $multiplier <= 1) {
                continue;
            }

            if ($start < $end) {
                $in = in_array($day, $days, true) && $now >= $start && $now < $end;
            } else {
                /* Past midnight: the evening of a listed day, or the early
                   morning after one. */
                $in = (in_array($day, $days, true) && $now >= $start)
                    || (in_array($previousDay, $days, true) && $now < $end);
            }

            if ($in && $multiplier > $best) {
                $best = $multiplier;
            }
        }

        return $best;
    }

    /** Whether a point is inside a zone's area ([{latitude, longitude}...]). */
    public static function inArea(float $lat, float $lng, $area): bool
    {
        if (!is_array($area) || count($area) < 3) {
            return false;
        }
        $points = [];
        foreach ($area as $p) {
            $y = ParcelPricing::num($p['latitude'] ?? null);
            $x = ParcelPricing::num($p['longitude'] ?? null);
            if ($y !== null && $x !== null) {
                $points[] = [$x, $y];
            }
        }
        $n = count($points);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $points[$i];
            [$xj, $yj] = $points[$j];
            if ((($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /** The highest busy-zone multiplier for the pickup point, or 1.
     *  $zones: zoneId => zone document (with `area`). */
    public static function busyZoneMultiplier(array $busyZones, array $zones, ?float $lat, ?float $lng): float
    {
        if ($lat === null || $lng === null) {
            return 1.0;
        }
        $best = 1.0;
        foreach ($busyZones as $row) {
            $multiplier = ParcelPricing::num($row['multiplier'] ?? null);
            $zone = $zones[(string) ($row['zoneId'] ?? '')] ?? null;
            if ($multiplier === null || $multiplier <= 1 || !$zone) {
                continue;
            }
            if ($multiplier > $best && self::inArea($lat, $lng, $zone['area'] ?? null)) {
                $best = $multiplier;
            }
        }

        return $best;
    }

    /** [multiplier, reason]: the higher of the busy hour and the busy zone
     *  (client, 5 Nov). reason is "busy_hour", "busy_zone" or null. */
    public static function multiplier(?array $settings, array $zones, ?float $lat, ?float $lng, \DateTimeInterface $local): array
    {
        if (!$settings) {
            return [1.0, null];
        }
        $hour = self::busyHourMultiplier(is_array($settings['busyHours'] ?? null) ? $settings['busyHours'] : [], $local);
        $zone = self::busyZoneMultiplier(is_array($settings['busyZones'] ?? null) ? $settings['busyZones'] : [], $zones, $lat, $lng);

        if ($hour <= 1 && $zone <= 1) {
            return [1.0, null];
        }

        return $hour >= $zone ? [$hour, 'busy_hour'] : [$zone, 'busy_zone'];
    }

    /** The price: (distance part + minutes x per minute) x multiplier, never
     *  below the minimum (which applies to the whole price). */
    public static function apply(float $distancePart, ?float $minutes, float $perMinute, float $multiplier, float $minimum = 0.0): float
    {
        $timePart = ($minutes !== null && $minutes > 0 && $perMinute > 0) ? $minutes * $perMinute : 0.0;

        return round(max(($distancePart + $timePart) * max(1.0, $multiplier), $minimum), 2);
    }

    /** The block an order records (spec section 5). Parcel and Delivery are
     *  fixed at booking: final = estimate, no actual minutes, no cap. */
    public static function block(float $km, ?float $minutes, float $perKm, float $perMinute, array $multiplier, float $price): array
    {
        return [
            'distanceKm' => round($km, 2),
            'estimatedMinutes' => $minutes !== null ? round($minutes, 1) : null,
            'actualMinutes' => null,
            'perKm' => round($perKm, 2),
            'perMinute' => round($perMinute, 2),
            'multiplier' => round((float) $multiplier[0], 2),
            'multiplierReason' => $multiplier[1],
            'capPercent' => null,
            'estimate' => $price,
            'final' => $price,
        ];
    }

    /** Whether any of it can change a price: a price per minute, or the
     *  region's traffic settings for this service. Nothing to record otherwise. */
    public static function matters(float $perMinute, ?array $settings): bool
    {
        return $perMinute > 0 || $settings !== null;
    }

    /* ------------------------------------------------------ live reads -- */

    /** The expected trip time in minutes, with traffic, from Google's Routes
     *  API - or null when Google gives none. Cached for 10 minutes per route
     *  (a checkout recalculates often); a failure is cached too, so a key
     *  without the Routes API does not slow every page. */
    public static function tripMinutes($lat1, $lng1, $lat2, $lng2): ?float
    {
        $coords = array_map(function ($v) {
            return ParcelPricing::num($v);
        }, [$lat1, $lng1, $lat2, $lng2]);
        if (in_array(null, $coords, true)) {
            return null;
        }
        [$aLat, $aLng, $bLat, $bLng] = $coords;

        $cacheKey = 'p103_route_' . md5(implode(',', array_map(function ($v) {
            return number_format($v, 4, '.', '');
        }, $coords)));

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached['minutes'];
        }

        $minutes = null;
        try {
            $key = self::googleKey();
            if ($key !== '') {
                $response = Http::timeout(5)->withHeaders([
                    'X-Goog-Api-Key' => $key,
                    'X-Goog-FieldMask' => 'routes.duration,routes.staticDuration,routes.distanceMeters',
                ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                    'origin' => ['location' => ['latLng' => ['latitude' => $aLat, 'longitude' => $aLng]]],
                    'destination' => ['location' => ['latLng' => ['latitude' => $bLat, 'longitude' => $bLng]]],
                    'travelMode' => 'DRIVE',
                    'routingPreference' => 'TRAFFIC_AWARE',
                ]);
                $duration = $response->successful() ? ($response->json()['routes'][0]['duration'] ?? null) : null;
                /* "1234s" */
                if (is_string($duration) && preg_match('/^(\d+(?:\.\d+)?)s$/', $duration, $m)) {
                    $minutes = round(((float) $m[1]) / 60, 1);
                }
            }
        } catch (\Throwable $e) {
            $minutes = null;
        }

        Cache::put($cacheKey, ['minutes' => $minutes], 600);

        return $minutes;
    }

    /** settings/googleMapKey.key, cached for an hour. Never printed. */
    private static function googleKey(): string
    {
        return (string) Cache::remember('p103_google_key', 3600, function () {
            $doc = FirestoreHelper::getDocument('settings/googleMapKey');
            return is_array($doc) ? trim((string) ($doc['key'] ?? '')) : '';
        });
    }

    /** regions/{id}, cached for a minute. */
    public static function region(string $regionId): ?array
    {
        if ($regionId === '') {
            return null;
        }
        $doc = Cache::remember('p103_region_' . $regionId, 60, function () use ($regionId) {
            return FirestoreHelper::getDocument('regions/' . $regionId) ?: [];
        });

        return $doc ?: null;
    }

    /** The busy zones' documents (zone/{id}), only when there are busy zones. */
    public static function zonesFor(?array $settings): array
    {
        $zones = [];
        foreach (is_array($settings['busyZones'] ?? null) ? $settings['busyZones'] : [] as $row) {
            $id = (string) ($row['zoneId'] ?? '');
            if ($id === '' || isset($zones[$id])) {
                continue;
            }
            $doc = Cache::remember('p103_zone_' . $id, 300, function () use ($id) {
                return FirestoreHelper::getDocument('zone/' . $id) ?: [];
            });
            if ($doc) {
                $zones[$id] = $doc;
            }
        }

        return $zones;
    }

    /** The multiplier for a booking now, in the region's local time. */
    public static function multiplierNow(?array $region, string $service, $lat, $lng): array
    {
        $settings = self::settingsFor($region, $service);
        if (!$settings) {
            return [1.0, null];
        }
        $local = new \DateTimeImmutable('now', self::timeZoneFor($region));

        return self::multiplier($settings, self::zonesFor($settings), ParcelPricing::num($lat), ParcelPricing::num($lng), $local);
    }

    /** A store's region: its regionId, else its zone's only region. */
    public static function storeRegionId(string $vendorId): string
    {
        if ($vendorId === '') {
            return '';
        }

        return (string) Cache::remember('p103_store_region_' . $vendorId, 300, function () use ($vendorId) {
            $store = FirestoreHelper::getDocument('vendors/' . $vendorId) ?: [];
            if (!empty($store['regionId'])) {
                return (string) $store['regionId'];
            }
            $zoneId = (string) ($store['zoneId'] ?? '');
            if ($zoneId === '') {
                return '';
            }
            $zone = FirestoreHelper::getDocument('zone/' . $zoneId) ?: [];
            $ids = is_array($zone['regionIds'] ?? null) ? $zone['regionIds']
                : (!empty($zone['regionId']) ? [$zone['regionId']] : []);

            return count($ids) === 1 ? (string) $ids[0] : '';
        });
    }

    /**
     * Delivery (store orders) - the region's per-km charge, with the time
     * part and the multiplier. A store's own delivery charge is kept as it
     * is (spec Q4, proposed): the caller only comes here for the region's.
     *
     * $distancePart is the charge as the site always worked it out (the
     * minimum within km, else km x per km); nothing changes it when there is
     * no price per minute and no multiplier. The pickup is the store.
     *
     * Returns [charge, trafficPricing block or null].
     */
    public static function forDelivery(float $distancePart, float $distance, string $unit, float $perKm, string $vendorId,
                                       $storeLat, $storeLng, $customerLat, $customerLng): array
    {
        try {
            $regionId = self::storeRegionId($vendorId);
            $region = self::region($regionId);
            $settings = self::settingsFor($region, 'delivery');
            $perMinute = self::deliveryPerMinute($regionId);
            if (!self::matters($perMinute, $settings)) {
                return [$distancePart, null];
            }

            $multiplier = self::multiplierNow($region, 'delivery', $storeLat, $storeLng);
            $minutes = $perMinute > 0 ? self::tripMinutes($storeLat, $storeLng, $customerLat, $customerLng) : null;
            $charge = self::apply($distancePart, $minutes, $perMinute, $multiplier[0]);
            $km = strtoupper($unit) === 'KM' ? $distance : $distance * 1.609344;

            return [$charge, self::block($km, $minutes, $perKm, $perMinute, $multiplier, $charge)];
        } catch (\Throwable $e) {
            /* Never let traffic pricing break a checkout: the old charge. */
            return [$distancePart, null];
        }
    }

    /** Delivery: the price per minute - the region's own block in
     *  settings/DeliveryCharge, else the global value, else 0. */
    public static function deliveryPerMinute(string $regionId): float
    {
        $settings = Cache::remember('p103_delivery_charge', 60, function () {
            return FirestoreHelper::getDocument('settings/DeliveryCharge') ?: [];
        });
        $own = ($regionId !== '' && is_array($settings['regions'][$regionId] ?? null))
            ? ParcelPricing::num($settings['regions'][$regionId]['per_minute_charge'] ?? null) : null;
        $value = $own ?? ParcelPricing::num($settings['per_minute_charge'] ?? null) ?? 0.0;

        return max(0.0, $value);
    }
}
