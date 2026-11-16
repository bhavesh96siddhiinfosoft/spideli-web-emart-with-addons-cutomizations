<?php

namespace App\Services;

use App\Helpers\FirestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: MyPVIT Gabon (PVit, docs.mypvit.pro), shared by every checkout of the
 * website.
 *
 *  - settings/mypvit_settings (admin > Payment Methods > MyPVIT): accountCode
 *    (ACC_...), password (API Secret), the URL codes secretCode / linkCode /
 *    statusCode, PVit's codes for our callback and redirect links, fees, isSandbox
 *    (the "Compte TEST" account is then the accountCode).
 *  - The secret key (X-Secret) is renewed here with the password (v2
 *    renew-secret, valid 3600 s) and cached; it never reaches a browser.
 *
 * Flow: start() asks PVit for a payment link (service WEB) with our reference.
 * PVit's link answer has no PVit transaction id; it comes with the callback,
 * which is not signed: the callback is accepted only from PVit's servers and is
 * then confirmed with PVit's status API before mypvit_transactions/{reference}
 * is marked verified. The return page accepts the payment only from that
 * verified record (same reference, amount, XAF).
 */
class MyPVit
{
    const API = 'https://api.mypvit.pro/';
    const CALLBACK_IPS = ['176.31.65.18', '176.31.65.20', '176.31.65.21', '13.59.249.167'];
    const SESSION = 'mypvit_payment';

    public static function settings()
    {
        try {
            return FirestoreHelper::getDocument('settings/mypvit_settings');
        } catch (\Throwable $e) {
            Log::warning('MyPVIT settings could not be read: ' . $e->getMessage());
            return null;
        }
    }

    public static function enabled(array $s = null)
    {
        $s = $s ?? self::settings();
        if (!$s || !(($s['isEnabled'] ?? null) === true || ($s['enable'] ?? null) === true)) {
            return false;
        }
        foreach (['accountCode', 'password', 'secretCode', 'linkCode', 'statusCode', 'callbackCode', 'successRedirectCode', 'failedRedirectCode'] as $k) {
            if (trim((string)($s[$k] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    protected static function code($v)
    {
        // A URL code, as shown in PVit's API list (letters and digits only).
        $v = trim((string)$v, " /\t\n");
        return preg_match('/^[A-Za-z0-9]{4,60}$/', $v) ? $v : '';
    }

    /* ------------------------------------------------------ the secret key */

    /** The X-Secret key for the settings' operation account (renewed when near expiry). */
    public static function secret(array $s, $fresh = false)
    {
        $key = 'mypvit_secret_' . md5((string)$s['accountCode'] . '|' . (string)$s['secretCode']);
        if (!$fresh && ($cached = Cache::get($key))) {
            return $cached;
        }
        $code = self::code($s['secretCode'] ?? '');
        if ($code === '') {
            throw new \RuntimeException('MyPVIT: invalid Renew Secret URL code');
        }
        $r = Http::asForm()->acceptJson()->timeout(20)->post(self::API . 'v2/' . $code . '/renew-secret', [
            'operationAccountCode' => (string)$s['accountCode'],
            'password' => (string)$s['password'],
        ]);
        $secret = (string)($r->json('secret') ?? '');
        if (!$r->successful() || $secret === '') {
            // Never log the answer (it may hold the key) or the password.
            Log::warning('MyPVIT renew-secret refused', ['http' => $r->status(), 'message' => $r->json('message')]);
            throw new \RuntimeException(trans('lang.mypvit_start_failed'));
        }
        $ttl = max(60, (int)($r->json('expires_in') ?? 3600) - 300);   // renew 5 minutes early
        Cache::put($key, $secret, $ttl);
        return $secret;
    }

    /* ------------------------------------------------ server-side Firestore */

    protected static function accessToken()
    {
        return Cache::remember('mypvit_sa_token', 3000, function () {
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
    }

    protected static function docUrl($reference)
    {
        $projectId = json_decode(file_get_contents(storage_path('app/firebase/credentials.json')), true)['project_id'] ?? '';
        return 'https://firestore.googleapis.com/v1/projects/' . $projectId . '/databases/(default)/documents/mypvit_transactions/' . $reference;
    }

    protected static function validReference($ref)
    {
        return is_string($ref) && preg_match('/^[A-Za-z0-9]{6,20}$/', $ref);
    }

    /** Merges fields into mypvit_transactions/{reference}. */
    public static function record($reference, array $fields)
    {
        if (!self::validReference($reference)) {
            return;
        }
        try {
            $enc = [];
            foreach ($fields as $k => $v) {
                $enc[$k] = is_bool($v) ? ['booleanValue' => $v] : (is_int($v) ? ['integerValue' => (string)$v]
                    : (is_float($v) ? ['doubleValue' => $v] : ($v === null ? ['nullValue' => null] : ['stringValue' => (string)$v])));
            }
            $enc['updatedAt'] = ['timestampValue' => gmdate('Y-m-d\TH:i:s\Z')];
            $mask = implode('&', array_map(function ($k) { return 'updateMask.fieldPaths=' . rawurlencode($k); }, array_keys($enc)));
            Http::withToken(self::accessToken())->timeout(10)->patch(self::docUrl($reference) . '?' . $mask, ['fields' => $enc]);
        } catch (\Throwable $e) {
            Log::warning('MyPVIT transaction not recorded: ' . $e->getMessage());
        }
    }

    /** mypvit_transactions/{reference} as a flat array, or null. */
    public static function readRecord($reference)
    {
        if (!self::validReference($reference)) {
            return null;
        }
        try {
            $r = Http::withToken(self::accessToken())->timeout(10)->get(self::docUrl($reference));
            if (!$r->successful()) {
                return null;
            }
            $out = [];
            foreach (($r->json('fields') ?? []) as $k => $v) {
                $out[$k] = $v['stringValue'] ?? ($v['booleanValue'] ?? (isset($v['integerValue']) ? (int)$v['integerValue'] : ($v['doubleValue'] ?? null)));
            }
            return $out;
        } catch (\Throwable $e) {
            Log::warning('MyPVIT transaction not read: ' . $e->getMessage());
            return null;
        }
    }

    /* ----------------------------------------------------------- payments */

    /**
     * Starts a payment for the current checkout and returns PVit's payment page
     * URL. Same arguments as MyCoolPay::start().
     */
    public static function start($flow, $amount, $currency, $reason, array $customer, $successUrl, $failUrl)
    {
        $s = self::settings();
        if (!self::enabled($s)) {
            throw new \RuntimeException(trans('lang.mypvit_unavailable'));
        }
        if (strtoupper((string)$currency) !== 'XAF') {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_currency', ['currency' => strtoupper((string)$currency)]) ?: 'XAF only');
        }
        $amount = (int)round((float)$amount);
        if ($amount <= 0) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_amount'));
        }
        // PVit: alphanumeric, unique, short (kept at 15 characters).
        $reference = 'SPD' . strtoupper(bin2hex(random_bytes(6)));
        $body = array_filter([
            'amount' => $amount,
            'reference' => $reference,
            'service' => 'WEB',
            'callback_url_code' => (string)$s['callbackCode'],
            'merchant_operation_account_code' => (string)$s['accountCode'],
            'transaction_type' => 'PAYMENT',
            'owner_charge' => in_array($s['ownerCharge'] ?? '', ['CUSTOMER', 'MERCHANT'], true) ? $s['ownerCharge'] : 'CUSTOMER',
            'owner_charge_operator' => in_array($s['operatorOwnerCharge'] ?? '', ['CUSTOMER', 'MERCHANT'], true) ? $s['operatorOwnerCharge'] : null,
            'success_redirection_url_code' => (string)$s['successRedirectCode'],
            'failed_redirection_url_code' => (string)$s['failedRedirectCode'],
            'customer_account_number' => preg_replace('/[^0-9]/', '', (string)($customer['phone'] ?? '')) ?: null,
            'free_info' => mb_substr((string)$reason, 0, 50),
            'product' => $flow,
        ], function ($v) { return $v !== null && $v !== ''; });

        $send = function ($secret) use ($s, $body) {
            return Http::acceptJson()->timeout(25)->withHeaders(['X-Secret' => $secret, 'X-Callback-MediaType' => 'application/json'])
                ->post(self::API . self::code($s['linkCode']) . '/link', $body);
        };
        $r = $send(self::secret($s));
        if ($r->status() === 401 || $r->status() === 403) {
            $r = $send(self::secret($s, true));   // the key was renewed elsewhere: once more with a new one
        }
        $url = (string)($r->json('url') ?? '');
        if (!$r->successful() || $url === '' || !preg_match('#^https://#', $url)) {
            Log::warning('MyPVIT link refused', ['http' => $r->status(), 'message' => $r->json('message'), 'flow' => $flow]);
            throw new \RuntimeException(trans('lang.mypvit_start_failed'));
        }
        Session::put(self::SESSION, [
            'flow' => $flow, 'reference' => $reference, 'amount' => $amount, 'currency' => 'XAF',
            'successUrl' => $successUrl, 'failUrl' => $failUrl,
            'confirmToken' => bin2hex(random_bytes(16)), 'confirmed' => false, 'startedAt' => time(),
        ]);
        Session::save();
        self::record($reference, ['flow' => $flow, 'amount' => $amount, 'currency' => 'XAF', 'status' => 'CREATED',
            'userEmail' => (string)($customer['email'] ?? ''), 'source' => 'website', 'sandbox' => (bool)($s['isSandbox'] ?? false)]);
        return $url;
    }

    /** PVit's status API for one of its transaction ids, or null. */
    public static function checkStatus($transactionId, array $s = null)
    {
        $s = $s ?? self::settings();
        if (!$s || !preg_match('/^[A-Za-z0-9_-]{4,60}$/', (string)$transactionId) || self::code($s['statusCode'] ?? '') === '') {
            return null;
        }
        $get = function ($secret) use ($s, $transactionId) {
            return Http::acceptJson()->timeout(20)->withHeaders(['X-Secret' => $secret])->get(self::API . self::code($s['statusCode']) . '/status', [
                'transactionId' => $transactionId, 'accountOperationCode' => (string)$s['accountCode'], 'transactionOperation' => 'PAYMENT',
            ]);
        };
        try {
            $r = $get(self::secret($s));
            if ($r->status() === 401 || $r->status() === 403) {
                $r = $get(self::secret($s, true));
            }
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable $e) {
            Log::warning('MyPVIT status failed: ' . $e->getMessage());
            return null;
        }
    }

    /** A verified record proves the pending payment: SUCCESS, same reference and amount. */
    public static function proves(array $record = null, array $pending = null)
    {
        return $record && $pending && !empty($record['verified'])
            && strtoupper((string)($record['status'] ?? '')) === 'SUCCESS'
            && abs((float)($record['amount'] ?? -1) - (float)$pending['amount']) < 0.01;
    }

    /** Once, for $flow's success page: the confirmed payment, or false. */
    public static function confirmed($token, $flow)
    {
        $p = Session::get(self::SESSION);
        if (!$p || empty($p['confirmed']) || ($p['flow'] ?? '') !== $flow || !is_string($token) || !hash_equals((string)$p['confirmToken'], $token)) {
            return false;
        }
        Session::forget(self::SESSION);
        Session::save();
        return $p;
    }
}
