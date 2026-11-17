<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finance F14: a Spideli Finance top-up paid with one of the client's
 * gateways (#92: MyCoolPay, MyPVIT, Sebpay, PI-SPI), whose secret keys stay on
 * this server. No Finance screen is added to the website:
 *
 *  1. the app writes finance_topups/{id} { userId, regionId, gateway, amount
 *     (minor units), currency, status: "created", createdAt } and opens
 *     /finance/topup/{id} in an in-app browser;
 *  2. check() holds it against the region (Finance on, its currency), the
 *     wallet settings (deposit on, method, min / max), the gateway (enabled,
 *     region) and the customer (no restriction); start() runs the gateway as
 *     for an order (flow "finance_topup");
 *  3. once the gateway confirms the payment, credit() writes in ONE Firestore
 *     transaction: the ledger posting (type "deposit", platform_{CUR} ->
 *     wallet_{uid}_{CUR}, created by its first credit), both balances, the
 *     top-up (status "completed") and a checked payment_receipts record.
 *
 * Firestore is reached with the service account (REST), never from the browser.
 */
class FinanceTopup
{
    /** gateway => [settings document, the Finance method families it provides] (admin App\Finance\Regions::METHOD_GATEWAYS). */
    const GATEWAYS = [
        'mycoolpay' => ['mycoolpay_settings', ['mobile_money', 'card'], 'MyCoolPay'],
        'mypvit' => ['mypvit_settings', ['mobile_money', 'card'], 'MyPVIT'],
        'sebpay_cm' => ['sebpay_cm_settings', ['mobile_money'], 'Sebpay Cameroun'],
        'sebpay_gn' => ['sebpay_gn_settings', ['mobile_money'], 'Sebpay Guinée'],
        'pispi' => ['pispi_settings', ['mobile_money'], 'PI-SPI'],
    ];
    const FLOW = 'finance_topup';
    const MAX_AGE = 1800;          // a top-up request is valid 30 minutes
    const ZERO_DECIMAL = ['XAF', 'XOF', 'GNF', 'RWF'];
    const ID = '/^[A-Za-z0-9_-]{8,64}$/';

    /* ------------------------------------------------------------ REST */

    protected static function projectId()
    {
        return json_decode(file_get_contents(storage_path('app/firebase/credentials.json')), true)['project_id'] ?? '';
    }

    protected static function base()
    {
        return 'https://firestore.googleapis.com/v1/projects/' . self::projectId() . '/databases/(default)/documents';
    }

    protected static function name($path)
    {
        return 'projects/' . self::projectId() . '/databases/(default)/documents/' . $path;
    }

    protected static function http()
    {
        $token = Cache::remember('finance_topup_sa_token', 3000, function () {
            $client = new \Google_Client();
            $client->setAuthConfig(storage_path('app/firebase/credentials.json'));
            $client->addScope('https://www.googleapis.com/auth/datastore');
            $client->refreshTokenWithAssertion();
            $t = $client->getAccessToken()['access_token'] ?? '';
            if ($t === '') {
                throw new \RuntimeException('no service account token');
            }
            return $t;
        });
        return Http::withToken($token)->acceptJson()->timeout(20);
    }

    public static function encode($v)
    {
        if (is_bool($v)) return ['booleanValue' => $v];
        if (is_int($v)) return ['integerValue' => (string)$v];
        if (is_float($v)) return ['doubleValue' => $v];
        if ($v === null) return ['nullValue' => null];
        if ($v instanceof \DateTimeInterface) return ['timestampValue' => $v->format('Y-m-d\TH:i:s.u\Z')];
        if (is_array($v)) {
            if ($v === [] || array_keys($v) === range(0, count($v) - 1)) {
                return ['arrayValue' => ['values' => array_map([self::class, 'encode'], $v)]];
            }
            return ['mapValue' => ['fields' => array_map([self::class, 'encode'], $v)]];
        }
        return ['stringValue' => (string)$v];
    }

    public static function decode(array $v)
    {
        $k = array_key_first($v);
        $x = $v[$k];
        switch ($k) {
            case 'integerValue': return (int)$x;
            case 'doubleValue': return (float)$x;
            case 'booleanValue': return (bool)$x;
            case 'nullValue': return null;
            case 'timestampValue': return new \DateTimeImmutable($x);
            case 'arrayValue': return array_map([self::class, 'decode'], $x['values'] ?? []);
            case 'mapValue': return array_map([self::class, 'decode'], $x['fields'] ?? []);
            default: return $x;
        }
    }

    protected static function fields(array $doc)
    {
        return array_map([self::class, 'decode'], $doc['fields'] ?? []);
    }

    public static function get($path)
    {
        $r = self::http()->get(self::base() . '/' . $path);
        if ($r->status() === 404) {
            return null;
        }
        if (!$r->successful()) {
            throw new \RuntimeException('Firestore read failed (' . $r->status() . ')');
        }
        return self::fields($r->json());
    }

    protected static function patch($path, array $fields)
    {
        $mask = implode('&', array_map(function ($k) { return 'updateMask.fieldPaths=' . rawurlencode($k); }, array_keys($fields)));
        $r = self::http()->patch(self::base() . '/' . $path . '?' . $mask . '&currentDocument.exists=true',
            ['fields' => array_map([self::class, 'encode'], $fields)]);
        if (!$r->successful()) {
            throw new \RuntimeException('Firestore write failed (' . $r->status() . ')');
        }
    }

    /* ------------------------------------------------------------ rules */

    public static function decimals($cur)
    {
        return in_array($cur, self::ZERO_DECIMAL, true) ? 0 : 2;
    }

    /** The gateway charges in the currency's main unit. */
    public static function major($minor, $cur)
    {
        $d = self::decimals($cur);
        return $d === 0 ? (int)$minor : round($minor / (10 ** $d), $d);
    }

    /**
     * The top-up and everything it is checked against, or a RuntimeException
     * whose message is shown to the customer.
     */
    /** The top-up's own fields, well formed, or a RuntimeException. */
    public static function load($id)
    {
        if (!preg_match(self::ID, (string)$id)) {
            throw new \RuntimeException(trans('lang.finance_topup_unknown'));
        }
        $t = self::get('finance_topups/' . $id);
        if (!$t) {
            throw new \RuntimeException(trans('lang.finance_topup_unknown'));
        }
        $uid = (string)($t['userId'] ?? '');
        $regionId = (string)($t['regionId'] ?? '');
        $gw = (string)($t['gateway'] ?? '');
        $cur = strtoupper((string)($t['currency'] ?? ''));
        $amount = $t['amount'] ?? null;
        if ($uid === '' || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $regionId) || !isset(self::GATEWAYS[$gw]) || !is_int($amount) || $amount <= 0 || !preg_match('/^[A-Z]{3}$/', $cur)) {
            throw new \RuntimeException(trans('lang.finance_topup_unknown'));
        }
        return ['topup' => $t, 'status' => (string)($t['status'] ?? ''), 'uid' => $uid, 'regionId' => $regionId, 'gateway' => $gw,
            'label' => self::GATEWAYS[$gw][2], 'currency' => $cur, 'amount' => $amount];
    }

    /** Before paying: the top-up may be paid now (region, settings, gateway, customer), or a RuntimeException. */
    public static function check($id)
    {
        $c = self::load($id);
        $t = $c['topup'];
        $uid = $c['uid'];
        $regionId = $c['regionId'];
        $gw = $c['gateway'];
        $cur = $c['currency'];
        $amount = $c['amount'];
        if ($c['status'] === 'completed') {
            throw new \RuntimeException(trans('lang.finance_topup_already_done'));
        }
        if (!in_array($c['status'], ['created', 'paying', 'failed'], true)) {
            throw new \RuntimeException(trans('lang.finance_topup_unknown'));
        }
        $created = $t['createdAt'] ?? null;
        if (!$created instanceof \DateTimeInterface || time() - $created->getTimestamp() > self::MAX_AGE) {
            throw new \RuntimeException(trans('lang.finance_topup_expired'));
        }
        $user = self::get('users/' . rawurlencode($uid));
        if (!$user) {
            throw new \RuntimeException(trans('lang.finance_topup_unknown'));
        }
        $restriction = $user['financeRestriction'] ?? null;
        if (is_array($restriction) && array_intersect((array)($restriction['scopes'] ?? ['all']), ['all', 'wallet'])) {
            throw new \RuntimeException(trans('lang.finance_topup_restricted'));
        }
        if (!in_array($regionId, (array)($user['regionIds'] ?? []), true)) {
            throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
        }
        $region = self::get('regions/' . $regionId);
        $currency = $region ? self::get('currencies/' . ($region['currencyId'] ?? '-')) : null;
        if (!$region || empty($region['financeEnabled']) || strtoupper((string)($currency['code'] ?? '')) !== $cur) {
            throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
        }
        $ws = self::get('finance_settings/' . $regionId . '_wallet') ?: [];
        $dep = (array)($ws['deposit'] ?? []);
        [$doc, $families, $label] = self::GATEWAYS[$gw];
        if (empty($dep['enabled']) || !array_intersect($families, (array)($dep['methods'] ?? []))) {
            throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
        }
        if (isset($dep['min'][$cur]) && $amount < (int)$dep['min'][$cur]) {
            throw new \RuntimeException(trans('lang.finance_topup_too_small'));
        }
        if (isset($dep['max'][$cur]) && $amount > (int)$dep['max'][$cur]) {
            throw new \RuntimeException(trans('lang.finance_topup_too_large'));
        }
        $gs = self::get('settings/' . $doc) ?: [];
        $enabled = ($gs['isEnabled'] ?? null) === true || ($gs['enable'] ?? null) === true;
        $regions = (array)($gs['regionIds'] ?? []);
        if (!$enabled || ($regions && !in_array($regionId, $regions, true))) {
            throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
        }
        return ['topup' => $t, 'user' => $user, 'uid' => $uid, 'regionId' => $regionId, 'gateway' => $gw,
            'label' => $label, 'currency' => $cur, 'amount' => $amount];
    }

    /** Starts the gateway; returns where to send the customer. */
    public static function start($id)
    {
        $c = self::check($id);
        self::patch('finance_topups/' . $id, ['status' => 'paying', 'startedAt' => new \DateTimeImmutable('now', new \DateTimeZone('UTC'))]);
        $major = self::major($c['amount'], $c['currency']);
        $customer = ['name' => trim(($c['user']['firstName'] ?? '') . ' ' . ($c['user']['lastName'] ?? '')) ?: ($c['user']['name'] ?? ''),
            'email' => (string)($c['user']['email'] ?? ''), 'phone' => (string)($c['user']['phoneNumber'] ?? '')];
        $ok = route('finance.topup.done', $id);
        $ko = route('finance.topup.failed', $id);
        $reason = 'Spideli Finance top-up';
        switch ($c['gateway']) {
            case 'mycoolpay': return MyCoolPay::start(self::FLOW, $major, $c['currency'], $reason, $customer, $ok, $ko);
            case 'mypvit': return MyPVit::start(self::FLOW, $major, $c['currency'], $reason, $customer, $ok, $ko);
            case 'sebpay_cm': return Sebpay::start('cm', self::FLOW, $major, $c['currency'], $reason, $customer, $ok, $ko);
            case 'sebpay_gn': return Sebpay::start('gn', self::FLOW, $major, $c['currency'], $reason, $customer, $ok, $ko);
            case 'pispi': return PiSpi::start(self::FLOW, $major, $c['currency'], $reason, $customer, $ok, $ko);
        }
        throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
    }

    /** The gateway's proof for this top-up (its success-page token), or false. */
    public static function proof($gateway, array $query)
    {
        switch ($gateway) {
            case 'mycoolpay': $p = MyCoolPay::confirmed($query['mycoolpay_token'] ?? null, self::FLOW); break;
            case 'mypvit': $p = MyPVit::confirmed($query['mypvit_token'] ?? null, self::FLOW); break;
            case 'sebpay_cm': case 'sebpay_gn': $p = Sebpay::confirmed($query['sebpay_token'] ?? null, self::FLOW); break;
            case 'pispi': $p = PiSpi::confirmed($query['pispi_token'] ?? null, self::FLOW); break;
            default: $p = false;
        }
        if (!is_array($p)) {
            return false;
        }
        $p['reference'] = (string)($p['reference'] ?? $p['transactionRef'] ?? '');
        return $p;
    }

    /**
     * Credits the Finance wallet for a paid top-up, once, in one Firestore
     * transaction. $paid is the gateway's confirmed payment (amount in the
     * main unit, currency, reference). Returns the ledger reference.
     */
    public static function credit($id, array $paid)
    {
        // Paid: credited whatever changed in the settings meanwhile (only the payment must match the top-up).
        $c = self::load($id);
        $cur = $c['currency'];
        if (strtoupper((string)($paid['currency'] ?? '')) !== $cur || abs((float)($paid['amount'] ?? -1) - (float)self::major($c['amount'], $cur)) > 0.001) {
            Log::warning('Finance top-up: the payment does not match the top-up', ['topup' => $id]);
            throw new \RuntimeException(trans('lang.mycoolpay_mismatch'));
        }
        $uid = $c['uid'];
        $amount = $c['amount'];
        $walletId = 'wallet_' . $uid . '_' . $cur;
        $platformId = 'platform_' . $cur;
        $http = self::http();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $tx = $http->post(self::base() . ':beginTransaction', ['options' => ['readWrite' => new \stdClass()]])->json('transaction');
            if (!$tx) {
                throw new \RuntimeException('Firestore transaction refused');
            }
            $read = $http->post(self::base() . ':batchGet', ['transaction' => $tx, 'documents' => [
                self::name('finance_topups/' . $id), self::name('financial_accounts/' . $walletId), self::name('financial_accounts/' . $platformId),
            ]])->json();
            $docs = [];
            foreach ((array)$read as $row) {
                if (isset($row['found'])) {
                    $docs[substr($row['found']['name'], strlen(self::name('')))] = self::fields($row['found']);
                }
            }
            $topup = $docs['finance_topups/' . $id] ?? null;
            if (!$topup) {
                throw new \RuntimeException(trans('lang.finance_topup_unknown'));
            }
            if (($topup['status'] ?? '') === 'completed') {
                return (string)($topup['ledgerReference'] ?? '');   // already credited (a second return)
            }
            $platform = $docs['financial_accounts/' . $platformId] ?? null;
            if (!$platform || ($platform['status'] ?? '') !== 'active' || ($platform['currency'] ?? '') !== $cur) {
                throw new \RuntimeException(trans('lang.finance_topup_unavailable'));
            }
            $wallet = $docs['financial_accounts/' . $walletId] ?? null;
            if ($wallet && (($wallet['status'] ?? '') !== 'active' || ($wallet['currency'] ?? '') !== $cur)) {
                throw new \RuntimeException(trans('lang.finance_topup_restricted'));
            }

            $txId = 'tx_' . (int)floor(microtime(true) * 1000) . '_' . self::random(8);
            $reference = 'SPF-' . gmdate('ymd') . '-' . self::random(6, 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
            $now = ['fieldPath' => 'updatedAt', 'setToServerValue' => 'REQUEST_TIME'];
            $lines = [
                ['accountId' => $platformId, 'direction' => 'debit', 'amount' => $amount],
                ['accountId' => $walletId, 'direction' => 'credit', 'amount' => $amount],
            ];
            $writes = [];
            $writes[] = ['update' => ['name' => self::name('ledger_transactions/' . $txId), 'fields' => array_map([self::class, 'encode'], [
                'id' => $txId, 'type' => 'deposit', 'status' => 'posted', 'reference' => $reference, 'currency' => $cur,
                'lines' => $lines, 'userIds' => [$uid], 'accountIds' => [$platformId, $walletId],
                'source' => ['kind' => 'finance_topups', 'id' => $id], 'funding' => $c['gateway'], 'regionId' => $c['regionId'],
                'description' => 'Top-up via ' . $c['label'], 'createdBy' => 'website:finance_topup',
            ])], 'currentDocument' => ['exists' => false],
                'updateTransforms' => [['fieldPath' => 'createdAt', 'setToServerValue' => 'REQUEST_TIME']]];
            if ($wallet) {
                $writes[] = ['update' => ['name' => self::name('financial_accounts/' . $walletId), 'fields' => ['lastTxId' => self::encode($txId)]],
                    'updateMask' => ['fieldPaths' => ['lastTxId']], 'currentDocument' => ['exists' => true],
                    'updateTransforms' => [['fieldPath' => 'balance', 'increment' => self::encode($amount)], $now]];
            } else {
                $writes[] = ['update' => ['name' => self::name('financial_accounts/' . $walletId), 'fields' => array_map([self::class, 'encode'], [
                    'id' => $walletId, 'kind' => 'wallet', 'userId' => $uid, 'currency' => $cur, 'balance' => $amount,
                    'normalSide' => 'credit', 'status' => 'active', 'lastTxId' => $txId,
                ])], 'currentDocument' => ['exists' => false],
                    'updateTransforms' => [['fieldPath' => 'createdAt', 'setToServerValue' => 'REQUEST_TIME'], $now]];
            }
            $writes[] = ['update' => ['name' => self::name('financial_accounts/' . $platformId), 'fields' => ['lastTxId' => self::encode($txId)]],
                'updateMask' => ['fieldPaths' => ['lastTxId']], 'currentDocument' => ['exists' => true],
                'updateTransforms' => [['fieldPath' => 'balance', 'increment' => self::encode(-$amount)], $now]];
            $writes[] = ['update' => ['name' => self::name('finance_topups/' . $id), 'fields' => array_map([self::class, 'encode'], [
                'status' => 'completed', 'ledgerTxId' => $txId, 'ledgerReference' => $reference, 'gatewayReference' => (string)($paid['reference'] ?? ''),
            ])], 'updateMask' => ['fieldPaths' => ['status', 'ledgerTxId', 'ledgerReference', 'gatewayReference']],
                'currentDocument' => ['exists' => true], 'updateTransforms' => [['fieldPath' => 'completedAt', 'setToServerValue' => 'REQUEST_TIME']]];
            $writes[] = ['update' => ['name' => self::name('payment_receipts/' . $id), 'fields' => array_map([self::class, 'encode'], [
                'id' => $id, 'userId' => $uid, 'regionId' => $c['regionId'], 'method' => $c['label'], 'gateway' => $c['gateway'],
                'reference' => (string)($paid['reference'] ?? ''), 'amount' => $amount, 'currency' => $cur, 'txId' => $txId,
                'source' => 'finance_topup', 'verified' => true, 'verifiedBy' => 'website:gateway_api',
            ])], 'currentDocument' => ['exists' => false],
                'updateTransforms' => [['fieldPath' => 'createdAt', 'setToServerValue' => 'REQUEST_TIME'], ['fieldPath' => 'verifiedAt', 'setToServerValue' => 'REQUEST_TIME']]];

            $r = $http->post(self::base() . ':commit', ['transaction' => $tx, 'writes' => $writes]);
            if ($r->successful()) {
                return $reference;
            }
            if ($r->status() !== 409 && $r->status() !== 400) {
                break;
            }
            // 409 (contention) / 400 (a precondition changed): read again and retry.
        }
        Log::error('Finance top-up: the wallet could not be credited', ['topup' => $id]);
        throw new \RuntimeException(trans('lang.finance_topup_credit_failed'));
    }

    /** $status "failed", or "paid_not_credited" (paid, not credited: listed on the admin Finance dashboard). */
    public static function fail($id, $why, $status = 'failed')
    {
        try {
            $t = preg_match(self::ID, (string)$id) ? self::get('finance_topups/' . $id) : null;
            if ($t && !in_array($t['status'] ?? '', ['completed', 'paid_not_credited'], true)) {
                self::patch('finance_topups/' . $id, ['status' => $status, 'failureReason' => mb_substr((string)$why, 0, 200)]);
            }
        } catch (\Throwable $e) {
            Log::warning('Finance top-up not marked failed: ' . $e->getMessage());
        }
    }

    protected static function random($n, $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        $s = '';
        for ($i = 0; $i < $n; $i++) {
            $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $s;
    }
}
