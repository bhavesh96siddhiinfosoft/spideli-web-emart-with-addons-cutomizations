<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * #114: a client buys a plan a provider sells (provider_subscription_plans), on the website, by wallet or with one of
 * the client's gateways (#92). Decisions (user, 19 Nov): own collections; the provider's share goes to their wallet at
 * once; wallet or gateways; commission = the provider's own rate, else the On-Demand section's (as for bookings).
 *
 *  1. offer($planId): the enabled plan, its provider, its region currency, the commission and the payment methods;
 *  2. checkout(): a server-side provider_subscription_checkouts/{id} ties the payment to the plan, the client and the
 *     amount; by wallet it completes at once, otherwise the gateway runs (flow "provider_subscription");
 *  3. complete(): ONE Firestore transaction: the client's wallet debit (wallet only), the provider's wallet credit,
 *     the two `wallet` lines, provider_subscriptions/{id}, provider_subscription_payments/{id} and the checkout
 *     (status "completed"). A second return finds it completed and writes nothing.
 *
 * Firestore is reached with the service account (REST, the helpers of FinanceTopup), never from the browser.
 */
class ProviderSubscription extends FinanceTopup
{
    const SUB_FLOW = 'provider_subscription';
    const CHECKOUT_MAX_AGE = 1800;
    /** gateway => the currencies it takes (the gateway services refuse the others). */
    const GATEWAY_CURRENCIES = ['mycoolpay' => ['XAF'], 'mypvit' => ['XAF'], 'sebpay_cm' => ['XAF'], 'sebpay_gn' => ['GNF'], 'pispi' => ['XOF']];

    /* ------------------------------------------------------------ the offer */

    /** The plan a client may buy now, or a RuntimeException whose message is shown. */
    public static function offer($planId)
    {
        if (!preg_match(self::ID, (string)$planId)) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        $plan = self::get('provider_subscription_plans/' . $planId);
        if (!$plan || ($plan['isEnable'] ?? null) !== true) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        $providerId = (string)($plan['providerId'] ?? '');
        $provider = preg_match('/^[A-Za-z0-9_-]{1,64}$/', $providerId) ? self::get('users/' . $providerId) : null;
        $price = is_numeric($plan['price'] ?? null) ? (float)$plan['price'] : 0.0;
        $days = (string)($plan['expiryDay'] ?? '') === '365' ? 365 : 30;
        if (!$provider || ($provider['role'] ?? '') !== 'provider' || $price <= 0) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        $regionId = (string)($plan['regionId'] ?? '');
        $region = preg_match('/^[A-Za-z0-9_-]{1,64}$/', $regionId) ? self::get('regions/' . $regionId) : null;
        $currency = $region && !empty($region['currencyId']) ? self::get('currencies/' . $region['currencyId']) : null;
        $code = strtoupper((string)($currency['code'] ?? ''));
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        $price = round($price, self::decimals($code));
        $plan['id'] = $planId;
        return [
            'plan' => $plan, 'planId' => $planId, 'provider' => $provider, 'providerId' => $providerId,
            'providerName' => trim(($provider['firstName'] ?? '') . ' ' . ($provider['lastName'] ?? '')),
            'price' => $price, 'days' => $days, 'regionId' => $regionId, 'sectionId' => (string)($plan['sectionId'] ?? ''),
            'currency' => $code, 'currencyData' => $currency ?: [],
            'commission' => self::commission($provider, (string)($plan['sectionId'] ?? ''), $price, $code),
        ];
    }

    /**
     * The platform's cut, taken OUT of the price: as for bookings, nothing when the global switch or the section's
     * commission is off; otherwise the provider's own rate when set, else the section's. Never more than the price.
     */
    public static function commission(array $provider, $sectionId, $price, $currency)
    {
        $none = ['amount' => 0, 'type' => 'fixed', 'rate' => 0];
        $global = self::get('settings/AdminCommission');
        if (!$global || ($global['isEnabled'] ?? null) !== true) {
            return $none;
        }
        $section = preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string)$sectionId) ? self::get('sections/' . $sectionId) : null;
        $sectionRate = is_array($section['adminCommision'] ?? null) ? $section['adminCommision'] : null;
        if (!$sectionRate || !self::on($sectionRate['enable'] ?? null)) {
            return $none;
        }
        $own = $provider['adminCommission'] ?? null;
        $rate = (is_array($own) && isset($own['commission']) && $own['commission'] !== '') ? $own : $sectionRate;
        $value = is_numeric($rate['commission'] ?? null) ? max(0.0, (float)$rate['commission']) : 0.0;
        $type = stripos((string)($rate['type'] ?? ''), 'percent') !== false ? 'percentage' : 'fixed';
        $amount = $type === 'percentage' ? $price * $value / 100 : $value;
        $amount = round(min(max($amount, 0.0), $price), self::decimals($currency));
        return ['amount' => $amount, 'type' => $type, 'rate' => $value];
    }

    protected static function on($v)
    {
        return $v === true || $v === 'true' || $v === 1 || $v === '1';
    }

    /** wallet, then each gateway that is on for the plan's region and takes its currency: [key => label]. */
    public static function methods(array $offer)
    {
        $methods = ['wallet' => trans('lang.provider_sub_wallet')];
        foreach (self::GATEWAYS as $gw => [$doc, $families, $label]) {
            if (!in_array($offer['currency'], self::GATEWAY_CURRENCIES[$gw], true)) {
                continue;
            }
            $s = self::get('settings/' . $doc) ?: [];
            $enabled = ($s['isEnabled'] ?? null) === true || ($s['enable'] ?? null) === true;
            $regions = (array)($s['regionIds'] ?? []);
            if ($enabled && (!$regions || in_array($offer['regionId'], $regions, true))) {
                $methods[$gw] = $label;
            }
        }
        return $methods;
    }

    /* ------------------------------------------------------------ checkout */

    /**
     * Opens a checkout. Returns ['checkoutId' => id, 'redirect' => url|null]; by wallet the caller completes it at
     * once (redirect null).
     */
    public static function checkout($planId, $customerId, $method)
    {
        $offer = self::offer($planId);
        $methods = self::methods($offer);
        if (!isset($methods[$method])) {
            throw new \RuntimeException(trans('lang.provider_sub_method_unavailable'));
        }
        $customer = preg_match('/^[A-Za-z0-9_-]{1,64}$/', (string)$customerId) ? self::get('users/' . $customerId) : null;
        if (!$customer) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        if ($customerId === $offer['providerId']) {
            throw new \RuntimeException(trans('lang.provider_sub_own_plan'));
        }
        $id = 'psc_' . (int)floor(microtime(true) * 1000) . '_' . self::random(10);
        $c = $offer['commission'];
        $snapshot = $offer['plan'];
        unset($snapshot['createdAt']);
        $doc = [
            'id' => $id, 'planId' => $offer['planId'], 'providerId' => $offer['providerId'], 'customerId' => (string)$customerId,
            'method' => $method, 'amount' => $offer['price'], 'currency' => $offer['currency'], 'days' => $offer['days'],
            'adminCommission' => $c['amount'], 'adminCommissionType' => $c['type'], 'adminCommissionRate' => $c['rate'],
            'providerEarning' => round($offer['price'] - $c['amount'], self::decimals($offer['currency'])),
            'plan' => $snapshot, 'regionId' => $offer['regionId'], 'sectionId' => $offer['sectionId'],
            'status' => $method === 'wallet' ? 'created' : 'paying', 'source' => 'website',
        ];
        $r = self::http()->post(self::base() . '/provider_subscription_checkouts?documentId=' . $id, [
            'fields' => array_map([self::class, 'encode'], $doc + ['createdAt' => new \DateTimeImmutable('now', new \DateTimeZone('UTC'))]),
        ]);
        if (!$r->successful()) {
            throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
        }
        if ($method === 'wallet') {
            return ['checkoutId' => $id, 'redirect' => null];
        }
        $who = ['name' => trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '')),
            'email' => (string)($customer['email'] ?? ''), 'phone' => (string)($customer['phoneNumber'] ?? '')];
        $ok = route('provider-subscription.done', $id);
        $ko = route('provider-subscription.failed', $id);
        $reason = 'Spideli subscription: ' . mb_substr((string)($offer['plan']['title'] ?? ''), 0, 60);
        $amount = $offer['price'];
        switch ($method) {
            case 'mycoolpay': $url = MyCoolPay::start(self::SUB_FLOW, $amount, $offer['currency'], $reason, $who, $ok, $ko); break;
            case 'mypvit': $url = MyPVit::start(self::SUB_FLOW, $amount, $offer['currency'], $reason, $who, $ok, $ko); break;
            case 'sebpay_cm': $url = Sebpay::start('cm', self::SUB_FLOW, $amount, $offer['currency'], $reason, $who, $ok, $ko); break;
            case 'sebpay_gn': $url = Sebpay::start('gn', self::SUB_FLOW, $amount, $offer['currency'], $reason, $who, $ok, $ko); break;
            case 'pispi': $url = PiSpi::start(self::SUB_FLOW, $amount, $offer['currency'], $reason, $who, $ok, $ko); break;
            default: throw new \RuntimeException(trans('lang.provider_sub_method_unavailable'));
        }
        return ['checkoutId' => $id, 'redirect' => $url];
    }

    /** The checkout, for its own customer only, or a RuntimeException. */
    public static function loadCheckout($id, $customerId)
    {
        if (!preg_match('/^psc_[0-9]+_[a-z0-9]{10}$/', (string)$id)) {
            throw new \RuntimeException(trans('lang.provider_sub_unknown'));
        }
        $c = self::get('provider_subscription_checkouts/' . $id);
        if (!$c || (string)($c['customerId'] ?? '') !== (string)$customerId) {
            throw new \RuntimeException(trans('lang.provider_sub_unknown'));
        }
        return $c;
    }

    /** The gateway's proof for this checkout (its success-page token), or false. */
    public static function gatewayProof($gateway, array $query)
    {
        switch ($gateway) {
            case 'mycoolpay': $p = MyCoolPay::confirmed($query['mycoolpay_token'] ?? null, self::SUB_FLOW); break;
            case 'mypvit': $p = MyPVit::confirmed($query['mypvit_token'] ?? null, self::SUB_FLOW); break;
            case 'sebpay_cm': case 'sebpay_gn': $p = Sebpay::confirmed($query['sebpay_token'] ?? null, self::SUB_FLOW); break;
            case 'pispi': $p = PiSpi::confirmed($query['pispi_token'] ?? null, self::SUB_FLOW); break;
            default: $p = false;
        }
        if (!is_array($p)) {
            return false;
        }
        $p['reference'] = (string)($p['reference'] ?? $p['transactionRef'] ?? '');
        return $p;
    }

    /* ------------------------------------------------------------ complete */

    /**
     * Completes a checkout once, in one Firestore transaction. $paid is the gateway's confirmed payment (null for a
     * wallet checkout). Returns the checkout as completed (subscriptionId, expiryDate).
     */
    public static function complete($id, $customerId, array $paid = null)
    {
        $c = self::loadCheckout($id, $customerId);
        if (($c['status'] ?? '') === 'completed') {
            return $c;
        }
        $cur = (string)$c['currency'];
        $amount = (float)$c['amount'];
        $byWallet = ($c['method'] ?? '') === 'wallet';
        if (!$byWallet) {
            if (!$paid || strtoupper((string)($paid['currency'] ?? '')) !== $cur || abs((float)($paid['amount'] ?? -1) - $amount) > 0.001) {
                Log::warning('Provider subscription: the payment does not match the checkout', ['checkout' => $id]);
                throw new \RuntimeException(trans('lang.mycoolpay_mismatch'));
            }
        } elseif (time() - (($c['createdAt'] ?? null) instanceof \DateTimeInterface ? $c['createdAt']->getTimestamp() : 0) > self::CHECKOUT_MAX_AGE) {
            throw new \RuntimeException(trans('lang.provider_sub_expired'));
        }
        $start = self::startFor((string)$c['customerId'], (string)$c['planId']);
        $expiry = $start->modify('+' . (int)$c['days'] . ' days');
        $http = self::http();
        $d = self::decimals($cur);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $tx = $http->post(self::base() . ':beginTransaction', ['options' => ['readWrite' => new \stdClass()]])->json('transaction');
            if (!$tx) {
                throw new \RuntimeException(trans('lang.provider_sub_failed'));
            }
            $read = $http->post(self::base() . ':batchGet', ['transaction' => $tx, 'documents' => [
                self::name('provider_subscription_checkouts/' . $id), self::name('users/' . $c['customerId']), self::name('users/' . $c['providerId']),
            ]])->json();
            $docs = [];
            foreach ((array)$read as $row) {
                if (isset($row['found'])) {
                    $docs[substr($row['found']['name'], strlen(self::name('')))] = self::fields($row['found']);
                }
            }
            $now = $docs['provider_subscription_checkouts/' . $id] ?? null;
            if (!$now) {
                throw new \RuntimeException(trans('lang.provider_sub_unknown'));
            }
            if (($now['status'] ?? '') === 'completed') {
                $http->post(self::base() . ':rollback', ['transaction' => $tx]);
                return $now;   // a second return: already done
            }
            $customer = $docs['users/' . $c['customerId']] ?? null;
            $provider = $docs['users/' . $c['providerId']] ?? null;
            if (!$customer || !$provider) {
                throw new \RuntimeException(trans('lang.provider_sub_unavailable'));
            }
            $earning = round((float)$c['providerEarning'], $d);
            $writes = [];
            $stamp = ['fieldPath' => 'date', 'setToServerValue' => 'REQUEST_TIME'];
            if ($byWallet) {
                $balance = self::number($customer['wallet_amount'] ?? 0);
                if ($balance + 0.0001 < $amount) {
                    $http->post(self::base() . ':rollback', ['transaction' => $tx]);
                    throw new \RuntimeException(trans('lang.provider_sub_insufficient'));
                }
                $writes[] = self::setField('users/' . $c['customerId'], 'wallet_amount', self::money($balance - $amount, $d));
                $writes[] = ['update' => ['name' => self::name('wallet/' . $id . '_c'), 'fields' => array_map([self::class, 'encode'], [
                    'id' => $id . '_c', 'user_id' => (string)$c['customerId'], 'amount' => self::money($amount, $d), 'isTopUp' => false,
                    'note' => 'Provider subscription', 'payment_method' => 'Wallet', 'payment_status' => 'success',
                    'transactionUser' => 'user', 'order_id' => $id,
                ])], 'currentDocument' => ['exists' => false], 'updateTransforms' => [$stamp]];
            }
            $providerBalance = self::number($provider['wallet_amount'] ?? 0);
            $writes[] = self::setField('users/' . $c['providerId'], 'wallet_amount', self::money($providerBalance + $earning, $d));
            $method = (string)$c['method'];
            $writes[] = ['update' => ['name' => self::name('wallet/' . $id . '_p'), 'fields' => array_map([self::class, 'encode'], [
                'id' => $id . '_p', 'user_id' => (string)$c['providerId'], 'amount' => self::money($earning, $d), 'isTopUp' => true,
                'note' => 'Subscription payment', 'payment_method' => $byWallet ? 'Wallet' : (self::GATEWAYS[$method][2] ?? $method),
                'payment_status' => 'success', 'transactionUser' => 'provider', 'serviceType' => 'ondemand-service', 'order_id' => $id,
            ])], 'currentDocument' => ['exists' => false], 'updateTransforms' => [$stamp]];
            $subId = 'ps_' . substr($id, 4);
            $payId = 'psp_' . substr($id, 4);
            $plan = is_array($c['plan'] ?? null) ? $c['plan'] : [];
            $writes[] = ['update' => ['name' => self::name('provider_subscriptions/' . $subId), 'fields' => array_map([self::class, 'encode'], [
                'id' => $subId, 'planId' => (string)$c['planId'], 'providerId' => (string)$c['providerId'], 'customerId' => (string)$c['customerId'],
                'plan' => $plan, 'startDate' => $start, 'expiryDate' => $expiry, 'status' => 'active', 'paymentId' => $payId,
                'regionId' => (string)($c['regionId'] ?? ''), 'sectionId' => (string)($c['sectionId'] ?? ''),
            ])], 'currentDocument' => ['exists' => false], 'updateTransforms' => [['fieldPath' => 'createdAt', 'setToServerValue' => 'REQUEST_TIME']]];
            $writes[] = ['update' => ['name' => self::name('provider_subscription_payments/' . $payId), 'fields' => array_map([self::class, 'encode'], [
                'id' => $payId, 'subscriptionId' => $subId, 'planId' => (string)$c['planId'], 'providerId' => (string)$c['providerId'],
                'customerId' => (string)$c['customerId'], 'planTitle' => (string)($plan['title'] ?? ''),
                'amount' => self::money($amount, $d), 'currency' => $cur,
                'adminCommission' => self::money((float)$c['adminCommission'], $d), 'adminCommissionType' => (string)$c['adminCommissionType'],
                'adminCommissionRate' => (float)$c['adminCommissionRate'], 'providerEarning' => self::money($earning, $d),
                'walletCredited' => true, 'payment_method' => $method, 'paymentReference' => (string)($paid['reference'] ?? ''),
                'status' => 'paid', 'regionId' => (string)($c['regionId'] ?? ''), 'sectionId' => (string)($c['sectionId'] ?? ''),
                'checkoutId' => $id,
            ])], 'currentDocument' => ['exists' => false], 'updateTransforms' => [['fieldPath' => 'createdAt', 'setToServerValue' => 'REQUEST_TIME']]];
            $writes[] = ['update' => ['name' => self::name('provider_subscription_checkouts/' . $id), 'fields' => array_map([self::class, 'encode'], [
                'status' => 'completed', 'subscriptionId' => $subId, 'paymentId' => $payId, 'expiryDate' => $expiry,
                'paymentReference' => (string)($paid['reference'] ?? ''),
            ])], 'updateMask' => ['fieldPaths' => ['status', 'subscriptionId', 'paymentId', 'expiryDate', 'paymentReference']],
                'currentDocument' => ['exists' => true], 'updateTransforms' => [['fieldPath' => 'completedAt', 'setToServerValue' => 'REQUEST_TIME']]];

            $r = $http->post(self::base() . ':commit', ['transaction' => $tx, 'writes' => $writes]);
            if ($r->successful()) {
                return array_merge($now, ['status' => 'completed', 'subscriptionId' => $subId, 'paymentId' => $payId, 'expiryDate' => $expiry]);
            }
            if ($r->status() !== 409 && $r->status() !== 400) {
                break;
            }
        }
        Log::error('Provider subscription could not be completed', ['checkout' => $id]);
        throw new \RuntimeException(trans('lang.provider_sub_failed'));
    }

    /** A renewal starts when the client's current subscription to the same plan ends, so no paid day is lost. */
    protected static function startFor($customerId, $planId)
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $r = self::http()->post(self::base() . ':runQuery', ['structuredQuery' => [
            'from' => [['collectionId' => 'provider_subscriptions']],
            'where' => ['compositeFilter' => ['op' => 'AND', 'filters' => [
                ['fieldFilter' => ['field' => ['fieldPath' => 'customerId'], 'op' => 'EQUAL', 'value' => ['stringValue' => $customerId]]],
                ['fieldFilter' => ['field' => ['fieldPath' => 'planId'], 'op' => 'EQUAL', 'value' => ['stringValue' => $planId]]],
            ]]],
        ]]);
        $latest = $now;
        foreach ((array)$r->json() as $row) {
            if (empty($row['document'])) {
                continue;
            }
            $s = self::fields($row['document']);
            $end = $s['expiryDate'] ?? null;
            if (($s['status'] ?? 'active') !== 'cancelled' && $end instanceof \DateTimeImmutable && $end > $latest) {
                $latest = $end->setTimezone(new \DateTimeZone('UTC'));
            }
        }
        return $latest;
    }

    /** A wallet_amount as stored (number, or a number in a string on older documents). */
    protected static function number($v)
    {
        return is_numeric($v) ? (float)$v : 0.0;
    }

    /** Whole currencies stay whole numbers in Firestore. */
    protected static function money($v, $decimals)
    {
        $v = round((float)$v, $decimals);
        return ($decimals === 0 || floor($v) == $v) ? (int)$v : $v;
    }

    protected static function setField($path, $field, $value)
    {
        return ['update' => ['name' => self::name($path), 'fields' => [$field => self::encode($value)]],
            'updateMask' => ['fieldPaths' => [$field]], 'currentDocument' => ['exists' => true]];
    }

    /** Marks an unfinished checkout failed (or paid_not_credited: paid at the gateway, not completed here). */
    public static function failCheckout($id, $customerId, $why, $status = 'failed', $paymentReference = '')
    {
        try {
            $c = self::loadCheckout($id, $customerId);
            if (!in_array($c['status'] ?? '', ['completed', 'paid_not_credited'], true)) {
                $fields = ['status' => $status, 'failureReason' => mb_substr((string)$why, 0, 200)];
                if ((string)$paymentReference !== '') {
                    $fields['paymentReference'] = mb_substr((string)$paymentReference, 0, 100);   // for the admin to find the payment
                }
                self::patch('provider_subscription_checkouts/' . $id, $fields);
            }
        } catch (\Throwable $e) {
            Log::warning('Provider subscription checkout not marked failed: ' . $e->getMessage());
        }
    }
}
