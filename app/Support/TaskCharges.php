<?php

namespace App\Support;

use App\Helpers\FirestoreHelper;

/**
 * Paying for a job's tasks (client point 88, APP-SPEC-MULTI-DAY-TASKS.md §4).
 *
 * What the customer owes for a task job right now, read from the booking and
 * its tasks when the payment starts - never the amount the page sends:
 *   taskPayment "per_task": the tasks done and not paid;
 *   taskPayment "end":      every unpaid task, once all of them are done.
 */
class TaskCharges
{
    const OPEN_STATUSES = ['Order Accepted', 'Order Assigned', 'Order Ongoing'];

    /** The tasks to pay now, from the booking and its tasks (as Firestore REST gives them). */
    public static function payable(array $booking, array $tasks): array
    {
        $tasks = array_values(array_filter($tasks, function ($t) {
            return is_array($t) && !empty($t['id']);
        }));
        $unpaid = array_values(array_filter($tasks, function ($t) {
            return ($t['paid'] ?? false) !== true;
        }));
        if (($booking['taskPayment'] ?? 'end') === 'per_task') {
            return array_values(array_filter($unpaid, function ($t) {
                return ($t['status'] ?? '') === 'done';
            }));
        }
        $allDone = $tasks && count(array_filter($tasks, function ($t) {
            return ($t['status'] ?? '') === 'done';
        })) === count($tasks);
        return $allDone ? $unpaid : [];
    }

    public static function total(array $tasks): float
    {
        return array_sum(array_map(function ($t) {
            return ExtraCharges::amount($t['price'] ?? 0);
        }, $tasks));
    }

    /**
     * What the signed-in customer owes on their task job: the tasks to pay and
     * their total. Null when the booking is not theirs, not a task job or not
     * open, or cannot be read.
     */
    public static function owedOn($orderId, $customerId): ?array
    {
        if (!is_string($orderId) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/', $orderId) || !$customerId) {
            return null;
        }
        $booking = FirestoreHelper::getDocument('provider_orders/' . $orderId);
        if (!$booking || ($booking['authorID'] ?? null) !== $customerId || ($booking['hasTasks'] ?? false) !== true
            || !in_array($booking['status'] ?? '', self::OPEN_STATUSES, true)) {
            return null;
        }
        $pay = self::payable($booking, FirestoreHelper::getCollection('provider_orders/' . $orderId . '/tasks'));
        return ['booking' => $booking, 'tasks' => $pay, 'amount' => self::total($pay)];
    }
}
