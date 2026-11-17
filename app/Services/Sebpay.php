<?php

namespace App\Services;

use App\Helpers\FirestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: Sebpay, one account per country (cm = Cameroun, XAF; gn = Guinée, GNF),
 * shared by every checkout of the website. Source: the official Sebpay SDK
 * (github.com/mjims/sebpay-sdk-py).
 *
 *  - settings/sebpay_{country}_settings: publicKey, secretKey, isSandbox,
 *    currency, regionIds, callbackToken... (admin > Payment Methods).
 *  - pk_test_ keys go to the sandbox server, pk_live_ keys to live; every call
 *    sends X-Public-Key and X-Secret-Key from this server only.
 *  - A payment is a hosted checkout session; our return and callback links go
 *    with it. The return is confirmed server-to-server (GET the session:
 *    transaction_status "approved"); the callback is signed (X-SebPay-Signature
 *    = hex HMAC-SHA256 of the raw body with the secret key) and kept in
 *    sebpay_transactions/{reference}.
 */
class Sebpay
{
    const LIVE = 'https://newapi.sebpay.africa/api/v1';
    const SANDBOX = 'https://api.sandbox.sebpay.bj/api/v1';
    const COUNTRIES = ['cm' => 'XAF', 'gn' => 'GNF'];
    const SESSION = 'sebpay_payment';
    const SIGNATURE_HEADER = 'X-SebPay-Signature';

    public static function settings($country)
    {
        if (!isset(self::COUNTRIES[$country])) {
            return null;
        }
        try {
            return FirestoreHelper::getDocument('settings/sebpay_' . $country . '_settings');
        } catch (\Throwable $e) {
            Log::warning('Sebpay settings could not be read: ' . $e->getMessage());
            return null;
        }
    }

    public static function enabled(array $s = null)
    {
        return $s && (($s['isEnabled'] ?? null) === true || ($s['enable'] ?? null) === true)
            && trim((string)($s['publicKey'] ?? '')) !== '' && trim((string)($s['secretKey'] ?? '')) !== '';
    }

    public static function baseUrl(array $s)
    {
        return strpos((string)$s['publicKey'], 'pk_test_') === 0 ? self::SANDBOX : self::LIVE;
    }

    protected static function call(array $s, $method, $path, array $body = null)
    {
        $req = Http::acceptJson()->timeout(30)->withHeaders([
            'X-Public-Key' => trim((string)$s['publicKey']),
            'X-Secret-Key' => trim((string)$s['secretKey']),
        ]);
        $r = $method === 'POST' ? $req->post(self::baseUrl($s) . $path, $body ?? []) : $req->get(self::baseUrl($s) . $path);
        $json = $r->json();
        if (!$r->successful() || (is_array($json) && ($json['success'] ?? null) === false) || !is_array($json['data'] ?? null)) {
            // Never log the keys; the error code and message only.
            Log::warning('Sebpay API refused', ['http' => $r->status(), 'code' => $json['errors']['code'] ?? null, 'message' => $json['message'] ?? null, 'path' => $path]);
            return null;
        }
        return $json['data'];
    }

    /**
     * Starts a payment for the current checkout and returns Sebpay's checkout
     * URL. Same arguments as MyCoolPay::start(), with the country first.
     */
    public static function start($country, $flow, $amount, $currency, $reason, array $customer, $successUrl, $failUrl)
    {
        $s = self::settings($country);
        if (!self::enabled($s)) {
            throw new \RuntimeException(trans('lang.sebpay_unavailable'));
        }
        $want = strtoupper((string)($s['currency'] ?? self::COUNTRIES[$country]));
        if (strtoupper((string)$currency) !== $want) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_currency', ['currency' => strtoupper((string)$currency)]));
        }
        $amount = (int)round((float)$amount);   // XAF and GNF have no decimals
        if ($amount <= 0) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_amount'));
        }
        $reference = 'SPD-' . strtoupper($country) . '-' . strtoupper(bin2hex(random_bytes(8)));
        $body = array_filter([
            'amount' => $amount,
            'currency' => $want,
            'redirect_url' => route('sebpay.return', $country),
            'callback_url' => (string)($s['callbackUrl'] ?? '') ?: null,
            'external_reference' => $reference,
            'customer_name' => mb_substr(trim((string)($customer['name'] ?? '')), 0, 100) ?: null,
            'customer_email' => filter_var($customer['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            'customer_phone' => preg_replace('/[^0-9+]/', '', (string)($customer['phone'] ?? '')) ?: null,
            'metadata' => ['flow' => $flow, 'source' => 'website', 'reason' => mb_substr((string)$reason, 0, 80)],
        ], function ($v) { return $v !== null && $v !== ''; });
        $data = self::call($s, 'POST', '/checkout/sessions', $body);
        $url = (string)($data['checkout_url'] ?? '');
        if (!$data || $url === '' || !preg_match('#^https://#', $url) || empty($data['id'])) {
            throw new \RuntimeException(trans('lang.sebpay_start_failed'));
        }
        Session::put(self::SESSION, [
            'country' => $country, 'flow' => $flow, 'sessionId' => (string)$data['id'], 'reference' => $reference,
            'amount' => $amount, 'currency' => $want, 'successUrl' => $successUrl, 'failUrl' => $failUrl,
            'confirmToken' => bin2hex(random_bytes(16)), 'confirmed' => false, 'startedAt' => time(),
        ]);
        Session::save();
        self::record($reference, ['country' => $country, 'flow' => $flow, 'amount' => $amount, 'currency' => $want, 'status' => 'open',
            'sessionId' => (string)$data['id'], 'userEmail' => (string)($customer['email'] ?? ''), 'source' => 'website',
            'sandbox' => self::baseUrl($s) === self::SANDBOX]);
        return $url;
    }

    /** The session as Sebpay knows it (by its id or our reference), or null. */
    public static function retrieve($country, $idOrReference)
    {
        $s = self::settings($country);
        if (!$s || trim((string)($s['publicKey'] ?? '')) === '' || !preg_match('/^[A-Za-z0-9_-]{4,100}$/', (string)$idOrReference)) {
            return null;
        }
        return self::call($s, 'GET', '/checkout/sessions/' . rawurlencode($idOrReference));
    }

    /** Sebpay's session proves the pending payment: approved, same reference, amount and currency. */
    public static function proves(array $session = null, array $pending = null)
    {
        if (!$session || !$pending) {
            return false;
        }
        return strtolower((string)($session['transaction_status'] ?? '')) === 'approved'
            && (!isset($session['external_reference']) || (string)$session['external_reference'] === (string)$pending['reference'])
            && strtoupper((string)($session['currency'] ?? $pending['currency'])) === strtoupper((string)$pending['currency'])
            && abs((float)($session['amount'] ?? -1) - (float)$pending['amount']) < 0.01;
    }

    /** X-SebPay-Signature: hex HMAC-SHA256 of the raw body with the secret key. */
    public static function verifySignature($rawBody, $signature, $secretKey)
    {
        if (!is_string($signature) || $signature === '' || trim((string)$secretKey) === '') {
            return false;
        }
        return hash_equals(hash_hmac('sha256', (string)$rawBody, trim((string)$secretKey)), strtolower(trim($signature)));
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

    /* --------------------------------------- server-side Firestore (a trace) */

    protected static function accessToken()
    {
        return Cache::remember('sebpay_sa_token', 3000, function () {
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

    public static function record($reference, array $fields)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', (string)$reference)) {
            return;
        }
        try {
            $projectId = json_decode(file_get_contents(storage_path('app/firebase/credentials.json')), true)['project_id'] ?? '';
            $enc = [];
            foreach ($fields as $k => $v) {
                $enc[$k] = is_bool($v) ? ['booleanValue' => $v] : (is_int($v) ? ['integerValue' => (string)$v]
                    : (is_float($v) ? ['doubleValue' => $v] : ($v === null ? ['nullValue' => null] : ['stringValue' => (string)$v])));
            }
            $enc['updatedAt'] = ['timestampValue' => gmdate('Y-m-d\TH:i:s\Z')];
            $mask = implode('&', array_map(function ($k) { return 'updateMask.fieldPaths=' . rawurlencode($k); }, array_keys($enc)));
            Http::withToken(self::accessToken())->timeout(10)->patch('https://firestore.googleapis.com/v1/projects/' . $projectId
                . '/databases/(default)/documents/sebpay_transactions/' . $reference . '?' . $mask, ['fields' => $enc]);
        } catch (\Throwable $e) {
            Log::warning('Sebpay transaction not recorded: ' . $e->getMessage());
        }
    }
}
