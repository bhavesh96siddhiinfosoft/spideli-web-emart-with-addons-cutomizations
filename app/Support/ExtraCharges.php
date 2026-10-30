<?php

namespace App\Support;

use App\Helpers\FirestoreHelper;

/**
 * Several extra charges per booking (client point 81, EXTRA-CHARGES-SPEC.md).
 *
 * The server half of the browser helpers in layouts/footer (extraChargesOf and
 * friends): the amount a customer pays is read here from the booking itself
 * when the payment starts, never taken from the page.
 *
 *   provider_orders.extraChargesList  [{amount, description, addedAt, paid}]
 *
 * THE RULE: the list when there is one (even []), else the older single charge
 * (extraCharges + extraChargesDescription, paid when extraPaymentStatus is
 * true), else none.
 */
class ExtraCharges
{
    public static function amount($value): float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : 0.0;
        }
        $value = trim((string) $value);   // as the apps read it: no comma decimal
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /** Every charge of a booking, oldest first. addedAt stays as Firestore REST gives it (an ISO string) or null. */
    public static function of(?array $booking): array
    {
        if (!$booking) {
            return [];
        }
        if (array_key_exists('extraChargesList', $booking) && is_array($booking['extraChargesList'])) {
            $charges = [];
            foreach ($booking['extraChargesList'] as $c) {
                if (!is_array($c)) {
                    continue;
                }
                $amount = self::amount($c['amount'] ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $charges[] = [
                    'amount' => $amount,
                    'description' => trim((string) ($c['description'] ?? '')),
                    'addedAt' => isset($c['addedAt']) && $c['addedAt'] !== '' ? $c['addedAt'] : null,
                    'paid' => ($c['paid'] ?? false) === true,
                ];
            }
            return $charges;
        }
        $amount = self::amount($booking['extraCharges'] ?? 0);
        if ($amount <= 0) {
            return [];
        }
        return [[
            'amount' => $amount,
            'description' => trim((string) ($booking['extraChargesDescription'] ?? '')),
            'addedAt' => null,
            'paid' => ($booking['extraPaymentStatus'] ?? false) === true,
        ]];
    }

    public static function unpaid(?array $booking): array
    {
        return array_values(array_filter(self::of($booking), function ($c) {
            return !$c['paid'];
        }));
    }

    public static function total(array $charges): float
    {
        return array_sum(array_column($charges, 'amount'));
    }

    /**
     * What the signed-in customer owes on their booking right now: the booking,
     * the unpaid charges (to mark exactly those paid afterwards) and their total.
     * Null when the booking is not theirs or cannot be read.
     */
    public static function owedOn($orderId, $customerId): ?array
    {
        if (!is_string($orderId) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/', $orderId) || !$customerId) {
            return null;
        }
        $booking = FirestoreHelper::getDocument('provider_orders/' . $orderId);
        if (!$booking || ($booking['authorID'] ?? null) !== $customerId) {
            return null;
        }
        $charged = self::unpaid($booking);
        return ['booking' => $booking, 'charged' => $charged, 'amount' => self::total($charged)];
    }
}
