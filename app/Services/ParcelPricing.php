<?php

namespace App\Services;

/**
 * Point 41 (and 65) - pricing a parcel the way the customer app does.
 *
 * The rules are fixed in the admin panel's docs/app-spec-parcel-pricing.md and
 * APP-DEV-BRIEF-12-10-2026-PARCEL-PRICING.md; the field names below are that
 * contract, shared with the app, and must not be renamed.
 *
 * Pure: it is handed the region, the carriers and the weight bands, and reads
 * nothing itself, so every rule can be tested without Firestore.
 *
 * EVERY AMOUNT IS IN THE REGION'S OWN CURRENCY. Nothing here converts.
 */
class ParcelPricing
{
    public const SCOPES = ['city', 'intercity', 'intercountry'];
    public const METHODS = ['home', 'pickup_point'];

    /** The option meaning "no carrier": Spideli's own drivers, priced by the
     *  admin's weight bands. Document 1's "default setting" for a city. */
    public const DEFAULT_KEY = 'default';

    /** A number, or null when the value is not set. Older carrier prices are
     *  strings ("20"); null, "" and anything non-numeric mean "not set". */
    public static function num($value): ?float
    {
        if ($value === null || $value === '' || is_bool($value) || is_array($value)) {
            return null;
        }
        $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        return is_numeric($value) ? (float) $value : null;
    }

    /** A carrier's own value for this region if set, else the region's
     *  default, else 0. NULL is "not set"; 0 is "free" - they differ. */
    public static function pick(?array $carrierRow, ?array $regionPricing, string $key): float
    {
        $own = $carrierRow ? self::num($carrierRow[$key] ?? null) : null;
        if ($own !== null) {
            return $own;
        }
        $default = $regionPricing ? self::num($regionPricing[$key] ?? null) : null;

        return $default ?? 0.0;
    }

    /** The size divisor: the carrier's for this region, else the region's.
     *  Only a positive number counts - none means size is not used. */
    public static function divisor(?array $carrierRow, ?array $regionPricing): ?float
    {
        foreach ([$carrierRow, $regionPricing] as $source) {
            $value = $source ? self::num($source['volumetricDivisor'] ?? null) : null;
            if ($value !== null && $value > 0) {
                return $value;
            }
        }

        return null;
    }

    /** Real, size-based and chargeable weight, each rounded to 2 decimals.
     *  Size counts only with a divisor and all three dimensions. */
    public static function weights(?float $actualKg, ?array $dims, ?float $divisor): array
    {
        $actual = max(0.0, (float) ($actualKg ?? 0));
        $volumetric = 0.0;

        $l = self::num($dims['l'] ?? null);
        $w = self::num($dims['w'] ?? null);
        $h = self::num($dims['h'] ?? null);
        if ($divisor && $l > 0 && $w > 0 && $h > 0) {
            $volumetric = ($l * $w * $h) / $divisor;
        }

        return [
            'actualKg' => round($actual, 2),
            'volumetricKg' => round($volumetric, 2),
            'chargeableKg' => round(max($actual, $volumetric), 2),
        ];
    }

    /** Reads a band title the way the admin screen does: "0.1-1KG", "5 KG",
     *  "+15 KG". Returns [lo, hi] (hi null = no upper limit) or null. */
    public static function parseBandTitle($title): ?array
    {
        $text = str_replace(',', '.', (string) $title);
        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $text, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        if (preg_match('/^\s*\+\s*(\d+(?:\.\d+)?)/', $text, $m)) {
            return [(float) $m[1], null];
        }
        if (preg_match('/(\d+(?:\.\d+)?)/', $text, $m)) {
            return [null, (float) $m[1]];
        }

        return null;
    }

    /** A band's [min, max] in kg: minKg / maxKg when the admin has saved them,
     *  else read from the title. Null when neither works. */
    public static function bandRange(array $band): ?array
    {
        $min = self::num($band['minKg'] ?? null);
        $max = self::num($band['maxKg'] ?? null);
        if ($min !== null || $max !== null) {
            return [$min ?? 0.0, $max];
        }
        $parsed = self::parseBandTitle($band['title'] ?? '');

        return $parsed ? [$parsed[0] ?? 0.0, $parsed[1]] : null;
    }

    /** The weight a band stands for when the customer gave no kilograms: its
     *  top, or its bottom for the open-ended band. */
    public static function bandNominalKg(array $band): ?float
    {
        $range = self::bandRange($band);
        if (!$range) {
            return null;
        }

        return $range[1] ?? $range[0];
    }

    /** Agreed 12 Oct, decision 2: the band the customer chose, MOVED UP when
     *  the parcel's chargeable weight belongs in a heavier one. Never down -
     *  a customer may choose to pay for more than the parcel weighs. */
    public static function effectiveBand(array $bands, ?array $selected, float $chargeableKg): ?array
    {
        $containing = null;
        foreach ($bands as $band) {
            $range = self::bandRange($band);
            if (!$range) {
                continue;
            }
            [$min, $max] = $range;
            if ($chargeableKg >= $min && ($max === null || $chargeableKg <= $max)) {
                $containing = $band;
                break;
            }
        }

        if (!$selected) {
            return $containing;
        }
        if (!$containing) {
            return $selected;
        }

        $top = function ($band) {
            $range = self::bandRange($band);
            return $range ? ($range[1] ?? INF) : 0;
        };

        return $top($containing) > $top($selected) ? $containing : $selected;
    }

    /** Whether a carrier may be offered for this parcel. */
    public static function carrierOffered(array $carrier, string $regionId, string $scope, float $chargeableKg): bool
    {
        if (($carrier['publish'] ?? false) !== true) {
            return false;
        }

        /* As the app (PARCEL-MANAGEMENT-FLOW.md 1.3): a carrier explicitly
         * marked unverified is not offered; a missing flag counts as verified. */
        if (($carrier['isVerified'] ?? true) === false) {
            return false;
        }

        /* Agreed 12 Oct, decision 3: only the customer's region. A carrier
         * with no regions serves everywhere. */
        $regions = $carrier['regionIds'] ?? [];
        if (!empty($regions) && !in_array($regionId, $regions, true)) {
            return false;
        }

        /* Point 65. None recorded = all three. */
        $scopes = $carrier['deliveryScopes'] ?? [];
        if (!empty($scopes) && !in_array($scope, $scopes, true)) {
            return false;
        }

        $maxWeight = self::num($carrier['maxWeight'] ?? null);
        if ($maxWeight !== null && $maxWeight > 0 && $chargeableKg > $maxWeight) {
            return false;
        }

        return true;
    }

    /** Every option the customer can choose from, cheapest first, with the
     *  default (Spideli drivers) first when it is offered.
     *
     *  $ctx: regionId, scope, km, weightKg (nullable), dimensions (nullable),
     *        selectedBandId, pickupMethod, deliveryMethod, currency (code). */
    public static function quotes(array $ctx, ?array $region, array $carriers, array $bands): array
    {
        $regionId = (string) ($ctx['regionId'] ?? '');
        $scope = in_array($ctx['scope'] ?? '', self::SCOPES, true) ? $ctx['scope'] : 'city';
        $km = max(0.0, (float) ($ctx['km'] ?? 0));
        $pickupMethod = in_array($ctx['pickupMethod'] ?? '', self::METHODS, true) ? $ctx['pickupMethod'] : 'home';
        $deliveryMethod = in_array($ctx['deliveryMethod'] ?? '', self::METHODS, true) ? $ctx['deliveryMethod'] : 'home';
        $regionPricing = is_array($region['parcelPricing'] ?? null) ? $region['parcelPricing'] : null;

        $selected = null;
        foreach ($bands as $band) {
            if (($band['id'] ?? null) === ($ctx['selectedBandId'] ?? null)) {
                $selected = $band;
            }
        }

        /* The customer's kilograms, or what the chosen band stands for. */
        $actualKg = self::num($ctx['weightKg'] ?? null);
        if ($actualKg === null || $actualKg <= 0) {
            $actualKg = $selected ? self::bandNominalKg($selected) : null;
        }

        $fixedTax = 0.0;
        if ($scope === 'intercity') {
            $fixedTax = self::num($regionPricing['intercityFixedTax'] ?? null) ?? 0.0;
        } elseif ($scope === 'intercountry') {
            $fixedTax = self::num($regionPricing['intercountryFixedTax'] ?? null) ?? 0.0;
        }

        $build = function (?array $carrierRow, float $price, string $source, array $weights, ?float $divisor, ?array $band) use ($regionPricing, $pickupMethod, $deliveryMethod, $fixedTax, $ctx) {
            $pickupCharge = self::pick($carrierRow, $regionPricing,
                $pickupMethod === 'home' ? 'homePickupCharge' : 'pointDropoffCharge');
            $deliveryCharge = self::pick($carrierRow, $regionPricing,
                $deliveryMethod === 'home' ? 'homeDeliveryCharge' : 'pointCollectionCharge');
            $options = round($pickupCharge + $deliveryCharge, 2);

            return [
                'priceBreakdown' => [
                    'source' => $source,
                    'currency' => $ctx['currency'] ?? '',
                    'actualKg' => $weights['actualKg'],
                    'volumetricKg' => $weights['volumetricKg'],
                    'chargeableKg' => $weights['chargeableKg'],
                    'volumetricDivisor' => $divisor,
                    'carrierPrice' => $price,
                    'extraKgCharge' => 0,
                    'options' => $options,
                    'pickupCharge' => round($pickupCharge, 2),
                    'deliveryCharge' => round($deliveryCharge, 2),
                    'fixedTax' => round($fixedTax, 2),
                    'commission' => 0,
                ],
                'extras' => round($options + $fixedTax, 2),
                'band' => $band ? ['id' => $band['id'] ?? '', 'title' => $band['title'] ?? '',
                                   'delivery_charge' => self::num($band['delivery_charge'] ?? null) ?? 0] : null,
            ];
        };

        $out = [];

        /* The default - only within a city, as in the app. */
        if ($scope === 'city') {
            $divisor = self::divisor(null, $regionPricing);
            $weights = self::weights($actualKg, $ctx['dimensions'] ?? null, $divisor);
            $band = self::effectiveBand($bands, $selected, $weights['chargeableKg']);
            if ($band) {
                $rate = self::num($band['delivery_charge'] ?? null) ?? 0.0;
                /* round() to whole units, exactly as the website always has. */
                $price = (float) round($km * $rate);
                $out[] = array_merge([
                    'key' => self::DEFAULT_KEY,
                    'type' => 'default',
                    'carrierId' => null,
                    'carrierName' => null,
                    'photo' => '',
                    'eta' => null,
                ], $build(null, $price, 'default', $weights, $divisor, $band));
            }
        }

        $carrierQuotes = [];
        foreach ($carriers as $carrier) {
            $row = null;
            if (!empty($carrier['regionPricing'][$regionId]) && is_array($carrier['regionPricing'][$regionId])) {
                $row = $carrier['regionPricing'][$regionId];
            }
            $divisor = self::divisor($row, $regionPricing);
            $weights = self::weights($actualKg, $ctx['dimensions'] ?? null, $divisor);

            if (!self::carrierOffered($carrier, $regionId, $scope, $weights['chargeableKg'])) {
                continue;
            }

            /* The region's own prices, else the flat fallback fields. */
            $prices = $row ?: $carrier;
            $base = self::num($prices['baseCharge'] ?? null) ?? 0.0;
            $perKm = self::num($prices['perKmCharge'] ?? null) ?? 0.0;
            $perKg = self::num($prices['perKgCharge'] ?? null) ?? 0.0;
            $minimum = self::num($prices['minimumCharge'] ?? null) ?? 0.0;

            /* A carrier with no price at all would quote 0 - leave it out
             * rather than offer free delivery by accident. */
            if ($base <= 0 && $perKm <= 0 && $perKg <= 0 && $minimum <= 0) {
                continue;
            }

            $price = round(max($base + $perKm * $km + $perKg * $weights['chargeableKg'], $minimum), 2);
            $band = self::effectiveBand($bands, $selected, $weights['chargeableKg']);

            $carrierQuotes[] = array_merge([
                'key' => 'carrier:' . ($carrier['id'] ?? ''),
                'type' => 'carrier',
                'carrierId' => $carrier['id'] ?? '',
                'carrierName' => $carrier['name'] ?? '',
                /* The company account that receives this carrier's parcels. */
                'carrierOwnerId' => $carrier['ownerId'] ?? '',
                'photo' => $carrier['photo'] ?? '',
                'eta' => [
                    'min' => self::num($carrier['minDeliveryTime'] ?? null),
                    'max' => self::num($carrier['maxDeliveryTime'] ?? null),
                    'unit' => ($carrier['deliveryTimeUnit'] ?? 'hours') === 'days' ? 'days' : 'hours',
                ],
            ], $build($row, $price, 'rate_card', $weights, $divisor, $band));
        }

        usort($carrierQuotes, function ($a, $b) {
            return ($a['priceBreakdown']['carrierPrice'] + $a['extras'])
                <=> ($b['priceBreakdown']['carrierPrice'] + $b['extras']);
        });

        return array_merge($out, $carrierQuotes);
    }
}
