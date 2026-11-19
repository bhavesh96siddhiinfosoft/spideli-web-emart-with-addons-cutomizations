<?php

namespace App\Services;

/**
 * #112: car rental by the day.
 *
 * A "daily" rental package (rental_packages.packageType == "daily") holds per-day values: baseFare = price of one day,
 * includedDistance = km per day, includedHours = 24, and minDays / maxDays. A booking of N days copies the package
 * into the order with the totals for the whole booking (baseFare x N, includedHours 24 x N, includedDistance x N),
 * so every existing total (subTotal + extra km + extra minutes) stays right. Hourly packages are unchanged.
 */
class RentalDays
{
    const NOTE_MAX = 500;
    const DAYS_MAX = 365;

    /**
     * Applies the day count and the note to the rental search data kept in the session.
     * Returns [data, error]; error is '' when the data is valid.
     */
    public static function apply(array $data): array
    {
        $data['bookingNote'] = self::cleanNote($data['bookingNote'] ?? '');
        $package = is_array($data['rentalPackageModel'] ?? null) ? $data['rentalPackageModel'] : [];

        if (($package['packageType'] ?? '') !== 'daily') {
            $data['rentalType'] = 'hourly';
            unset($data['rentalDays'], $data['returnDate']);
            return [$data, ''];
        }

        $minDays = self::wholeNumber($package['minDays'] ?? '1') ?? 1;
        $maxDays = self::wholeNumber($package['maxDays'] ?? (string) self::DAYS_MAX) ?? self::DAYS_MAX;
        $minDays = max(1, $minDays);
        $maxDays = min(self::DAYS_MAX, max($minDays, $maxDays));
        $days = self::wholeNumber($data['rentalDays'] ?? '');
        if ($days === null || $days < $minDays || $days > $maxDays) {
            return [$data, 'days'];
        }

        $dayFare = self::amount($package['baseFare'] ?? null);
        $kmPerDay = self::amount($package['includedDistance'] ?? null);
        if ($dayFare === null || $kmPerDay === null) {
            return [$data, 'package'];
        }

        $package['packageType'] = 'daily';
        $package['dayFare'] = self::str($dayFare);
        $package['kmPerDay'] = self::str($kmPerDay);
        $package['baseFare'] = self::str($dayFare * $days);
        $package['includedHours'] = (string) (24 * $days);
        $package['includedDistance'] = self::str($kmPerDay * $days);

        $data['rentalPackageModel'] = $package;
        $data['rentalType'] = 'daily';
        $data['rentalDays'] = $days;
        $data['baseFarePrice'] = $dayFare * $days;
        $start = isset($data['startDate']) ? strtotime((string) $data['startDate']) : false;
        $data['returnDate'] = $start ? date('Y-m-d h:i A', $start + $days * 86400) : '';

        return [$data, ''];
    }

    public static function cleanNote($note): string
    {
        $note = trim(str_replace(["\r\n", "\r"], "\n", (string) $note));
        return mb_substr($note, 0, self::NOTE_MAX);
    }

    private static function wholeNumber($value): ?int
    {
        $value = trim((string) $value);
        return preg_match('/^\d{1,4}$/', $value) ? (int) $value : null;
    }

    private static function amount($value): ?float
    {
        if (!is_numeric($value) || (float) $value < 0) {
            return null;
        }
        return (float) $value;
    }

    // Package values are strings in Firestore: keep "45000", not "45000.0".
    private static function str(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
        return $text === '' || $text === '-0' ? '0' : $text;
    }
}
