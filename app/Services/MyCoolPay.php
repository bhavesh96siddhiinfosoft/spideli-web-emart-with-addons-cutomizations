<?php

namespace App\Services;

use App\Helpers\FirestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: MyCoolPay Cameroun (my-coolpay.com), shared by every checkout of the
 * website. Source: the MyCoolPay merchant API documentation (14 Oct 2021) and
 * the official PHP SDK (github.com/mycoolpay/php-sdk).
 *
 *  - settings/mycoolpay_settings: the public part (isEnabled, publicKey,
 *    currency, regionIds...), written by the admin's Payment Methods page.
 *  - payment_secrets/mycoolpay: privateKey + callbackToken, staff only, read
 *    here with the service account (storage/app/firebase/credentials.json).
 *    The private key never reaches a browser.
 *
 * A checkout calls start(); MyCoolPay sends the customer back to
 * /mycoolpay/return/{result} (MyCoolPayController), which checks the status
 * with MyCoolPay before the checkout's success page accepts the payment
 * (confirmed()). MyCoolPay also posts each result once to
 * /mycoolpay/callback/{token}; every transaction is kept in
 * mycoolpay_transactions/{transaction_ref}.
 *
 * There is no sandbox: every payment is real.
 */
class MyCoolPay
{
    const API = 'https://my-coolpay.com/api/';
    const CALLBACK_IP = '15.236.140.89';
    const SESSION = 'mycoolpay_payment';

    /** The public settings, or null. */
    public static function settings()
    {
        try {
            return FirestoreHelper::getDocument('settings/mycoolpay_settings');
        } catch (\Throwable $e) {
            Log::warning('MyCoolPay settings could not be read: ' . $e->getMessage());
            return null;
        }
    }

    public static function enabled(array $settings = null)
    {
        $settings = $settings ?? self::settings();
        return $settings && (($settings['isEnabled'] ?? null) === true || ($settings['enable'] ?? null) === true)
            && trim((string)($settings['publicKey'] ?? '')) !== '';
    }

    /* ----------------------------------------------- server-side Firestore */

    protected static function accessToken()
    {
        return Cache::remember('mycoolpay_sa_token', 3000, function () {
            $path = storage_path('app/firebase/credentials.json');
            $client = new \Google_Client();
            $client->setAuthConfig($path);
            $client->addScope('https://www.googleapis.com/auth/datastore');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken()['access_token'] ?? '';
            if ($token === '') {
                throw new \RuntimeException('no service account token');
            }
            return $token;
        });
    }

    protected static function documentsUrl()
    {
        $projectId = json_decode(file_get_contents(storage_path('app/firebase/credentials.json')), true)['project_id'] ?? '';
        return 'https://firestore.googleapis.com/v1/projects/' . $projectId . '/databases/(default)/documents/';
    }

    /**
     * ['privateKey' => ..., 'callbackToken' => ...] or null. The admin saves
     * them in settings/mycoolpay_settings like the other gateways; a first
     * version kept them in payment_secrets/mycoolpay, still read as a fallback.
     */
    public static function secret()
    {
        $s = self::settings();
        if ($s && trim((string)($s['privateKey'] ?? '')) !== '' && trim((string)($s['callbackToken'] ?? '')) !== '') {
            return ['privateKey' => trim((string)$s['privateKey']), 'callbackToken' => trim((string)$s['callbackToken'])];
        }
        try {
            return Cache::remember('mycoolpay_secret', 300, function () {
                $r = Http::withToken(self::accessToken())->timeout(10)->get(self::documentsUrl() . 'payment_secrets/mycoolpay');
                if (!$r->successful()) {
                    return null;
                }
                $f = $r->json('fields') ?? [];
                return [
                    'privateKey' => $f['privateKey']['stringValue'] ?? '',
                    'callbackToken' => $f['callbackToken']['stringValue'] ?? '',
                ];
            });
        } catch (\Throwable $e) {
            Log::warning('MyCoolPay secret could not be read: ' . $e->getMessage());
            return null;
        }
    }

    /** Merges fields into mycoolpay_transactions/{ref} (a trace; never trusted by itself). */
    public static function record($transactionRef, array $fields)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{4,100}$/', (string)$transactionRef)) {
            return;
        }
        try {
            $encoded = [];
            foreach ($fields as $k => $v) {
                $encoded[$k] = is_bool($v) ? ['booleanValue' => $v]
                    : (is_int($v) ? ['integerValue' => (string)$v]
                    : (is_float($v) ? ['doubleValue' => $v]
                    : ($v === null ? ['nullValue' => null] : ['stringValue' => (string)$v])));
            }
            $encoded['updatedAt'] = ['timestampValue' => gmdate('Y-m-d\TH:i:s\Z')];
            $mask = implode('&', array_map(function ($k) { return 'updateMask.fieldPaths=' . rawurlencode($k); }, array_keys($encoded)));
            Http::withToken(self::accessToken())->timeout(10)
                ->patch(self::documentsUrl() . 'mycoolpay_transactions/' . $transactionRef . '?' . $mask, ['fields' => $encoded]);
        } catch (\Throwable $e) {
            Log::warning('MyCoolPay transaction not recorded: ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------ the API */

    protected static function api($publicKey)
    {
        return self::API . rawurlencode($publicKey);
    }

    /**
     * Starts a payment for the current checkout and returns the MyCoolPay
     * payment page URL. $flow names the checkout (checkout, parcel, rental,
     * ondemand, giftcard, extra_charge, service_charge, wallet); $successUrl is
     * that checkout's success page, $failUrl where to go back on failure.
     */
    public static function start($flow, $amount, $currency, $reason, array $customer, $successUrl, $failUrl)
    {
        $settings = self::settings();
        if (!self::enabled($settings)) {
            throw new \RuntimeException(trans('lang.mycoolpay_unavailable'));
        }
        $currency = strtoupper((string)$currency);
        $allowed = strtoupper((string)($settings['currency'] ?? 'XAF')) === 'EUR' ? ['XAF', 'EUR'] : ['XAF'];
        if (!in_array($currency, $allowed, true)) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_currency', ['currency' => $currency]));
        }
        // XAF has no decimals; MyCoolPay takes a number of francs.
        $amount = $currency === 'XAF' ? (int)round((float)$amount) : round((float)$amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_amount'));
        }
        $appRef = 'SPD-' . strtoupper($flow === 'wallet' ? 'WAL' : substr(preg_replace('/[^a-z]/i', '', $flow), 0, 3)) . '-' . bin2hex(random_bytes(8));
        $lang = in_array(app()->getLocale(), ['fr', 'en'], true) ? app()->getLocale() : 'fr';
        $payload = array_filter([
            'transaction_amount' => $amount,
            'transaction_currency' => $currency,
            'transaction_reason' => mb_substr((string)$reason, 0, 100),
            'app_transaction_ref' => $appRef,
            'customer_phone_number' => preg_replace('/[^0-9]/', '', (string)($customer['phone'] ?? '')) ?: null,
            'customer_name' => mb_substr(trim((string)($customer['name'] ?? '')), 0, 100) ?: null,
            'customer_email' => filter_var($customer['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'customer_lang' => $lang,
        ], function ($v) { return $v !== null && $v !== ''; });

        $r = Http::acceptJson()->timeout(20)->post(self::api($settings['publicKey']) . '/paylink', $payload);
        $body = $r->json() ?: [];
        if (!$r->successful() || ($body['status'] ?? '') !== 'success' || empty($body['payment_url']) || empty($body['transaction_ref'])) {
            Log::warning('MyCoolPay paylink refused', ['http' => $r->status(), 'message' => $body['message'] ?? null, 'flow' => $flow]);
            throw new \RuntimeException(trans('lang.mycoolpay_start_failed') . (!empty($body['message']) ? ' (' . $body['message'] . ')' : ''));
        }

        $confirmToken = bin2hex(random_bytes(16));
        Session::put(self::SESSION, [
            'flow' => $flow,
            'appRef' => $appRef,
            'transactionRef' => $body['transaction_ref'],
            'amount' => $amount,
            'currency' => $currency,
            'successUrl' => $successUrl,
            'failUrl' => $failUrl,
            'confirmToken' => $confirmToken,
            'confirmed' => false,
            'startedAt' => time(),
        ]);
        Session::save();
        self::record($body['transaction_ref'], [
            'appTransactionRef' => $appRef, 'flow' => $flow, 'amount' => $amount, 'currency' => $currency,
            'status' => 'CREATED', 'userEmail' => (string)($customer['email'] ?? ''), 'source' => 'website',
        ]);
        return $body['payment_url'];
    }

    /** GET /checkStatus/{ref}: the transaction as MyCoolPay knows it, or null. */
    public static function checkStatus($transactionRef, $publicKey = null)
    {
        $publicKey = $publicKey ?? (self::settings()['publicKey'] ?? '');
        if ($publicKey === '' || !preg_match('/^[A-Za-z0-9_-]{4,100}$/', (string)$transactionRef)) {
            return null;
        }
        try {
            $r = Http::acceptJson()->timeout(15)->get(self::api($publicKey) . '/checkStatus/' . $transactionRef);
            $body = $r->json();
            return ($r->successful() && ($body['status'] ?? '') === 'success') ? $body : null;
        } catch (\Throwable $e) {
            Log::warning('MyCoolPay checkStatus failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Whether MyCoolPay's answer proves the pending payment: SUCCESS, the same
     * reference, amount and currency as what this session asked.
     */
    public static function proves(array $status = null, array $pending = null)
    {
        if (!$status || !$pending) {
            return false;
        }
        return strtoupper((string)($status['transaction_status'] ?? '')) === 'SUCCESS'
            && (string)($status['transaction_ref'] ?? $pending['transactionRef']) === (string)$pending['transactionRef']
            && (!isset($status['app_transaction_ref']) || (string)$status['app_transaction_ref'] === (string)$pending['appRef'])
            && strtoupper((string)($status['transaction_currency'] ?? $pending['currency'])) === strtoupper((string)$pending['currency'])
            && abs((float)($status['transaction_amount'] ?? -1) - (float)$pending['amount']) < 0.01;
    }

    /**
     * For a checkout's success page: once, when $token is this session's
     * confirmed MyCoolPay payment for $flow, returns that payment
     * (transactionRef, appRef, amount, currency); otherwise false. The pending
     * payment is then cleared, so a reload cannot pay twice.
     */
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

    /**
     * The callback's checks, in MyCoolPay's order: its IP, our public key, then
     * md5(transaction_ref . transaction_type . transaction_amount .
     * transaction_currency . transaction_operator . private_key).
     */
    public static function verifyCallback(array $data, $ip, $publicKey, $privateKey)
    {
        if ($ip !== self::CALLBACK_IP) {
            return 'ip';
        }
        if ($publicKey === '' || !hash_equals((string)$publicKey, (string)($data['application'] ?? ''))) {
            return 'application';
        }
        if ($privateKey === '') {
            return 'no_private_key';
        }
        $expected = md5(($data['transaction_ref'] ?? '') . ($data['transaction_type'] ?? '') . ($data['transaction_amount'] ?? '')
            . ($data['transaction_currency'] ?? '') . ($data['transaction_operator'] ?? '') . $privateKey);
        if (!hash_equals($expected, strtolower((string)($data['signature'] ?? '')))) {
            return 'signature';
        }
        return null;
    }
}
