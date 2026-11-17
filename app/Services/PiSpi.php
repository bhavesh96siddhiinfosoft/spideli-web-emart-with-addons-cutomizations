<?php

namespace App\Services;

use App\Helpers\FirestoreHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: PI-SPI, the BCEAO instant payment platform (UEMOA, XOF), through the
 * API Business of the client's participant. Source: developer.pispi.bceao.int
 * (guides "Demandes de paiement", "Sécurité") and the official checkout SDK
 * @pi-spi/checkout, whose flow this follows:
 *
 *  1. a checkout calls start(): the payment waits in the session and the
 *     customer goes to our page /pispi/pay;
 *  2. the customer gives their alias (payment address, a UUID) and request()
 *     sends a payment request (RTP, category 521 e-commerce, to be paid within
 *     3 minutes) from our account alias (payeAlias) to theirs;
 *  3. they accept it in their bank app; the page asks poll(), which reads the
 *     request from PI-SPI until it is IRREVOCABLE (paid) or REJETE.
 *
 * settings/pispi_settings: apiUrl, tokenUrl, clientId, clientSecret, apiKey,
 * payeAlias, isSandbox, mtlsCert / mtlsKey (production), regionIds. Calls go
 * from this server only: OAuth2 client credentials, x-api-key, and the mTLS
 * certificate when the sandbox is off. PI-SPI webhooks need mTLS on our side,
 * so the status is read instead. Each request is kept in
 * pispi_transactions/{txId}.
 */
class PiSpi
{
    const SANDBOX_API = 'https://sandbox.api.pi-bceao.com/piz/v1';
    const CURRENCY = 'XOF';
    const SESSION = 'pispi_payment';
    const SCOPE = 'piz/demande_paiement.write piz/demande_paiement.read';
    const PAY_WITHIN = 150;      // seconds given to the customer (PI-SPI: under 3 minutes for e-commerce)
    const MAX_REQUESTS = 5;      // payment requests per checkout (a wrong alias can be corrected)
    const ALIAS = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function settings()
    {
        try {
            return FirestoreHelper::getDocument('settings/pispi_settings');
        } catch (\Throwable $e) {
            Log::warning('PI-SPI settings could not be read: ' . $e->getMessage());
            return null;
        }
    }

    public static function enabled(array $s = null)
    {
        if (!$s || !(($s['isEnabled'] ?? null) === true || ($s['enable'] ?? null) === true)) {
            return false;
        }
        foreach (['tokenUrl', 'clientId', 'clientSecret', 'apiKey', 'payeAlias'] as $k) {
            if (trim((string)($s[$k] ?? '')) === '') {
                return false;
            }
        }
        return self::sandbox($s) || (trim((string)($s['mtlsCert'] ?? '')) !== '' && trim((string)($s['mtlsKey'] ?? '')) !== '');
    }

    public static function sandbox(array $s)
    {
        return ($s['isSandbox'] ?? true) !== false;
    }

    public static function apiUrl(array $s)
    {
        $u = rtrim(trim((string)($s['apiUrl'] ?? '')), '/');
        return $u !== '' ? $u : self::SANDBOX_API;
    }

    /** Guzzle options: the mTLS certificate and key (production), written once to storage. */
    protected static function tls(array $s)
    {
        if (self::sandbox($s)) {
            return [];
        }
        $cert = trim((string)($s['mtlsCert'] ?? ''));
        $key = trim((string)($s['mtlsKey'] ?? ''));
        $dir = storage_path('app/pispi');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $h = substr(hash('sha256', $cert . "\n" . $key), 0, 16);
        $certFile = $dir . '/cert-' . $h . '.pem';
        $keyFile = $dir . '/key-' . $h . '.pem';
        if (!is_file($certFile) || !is_file($keyFile)) {
            foreach (glob($dir . '/*.pem') ?: [] as $old) {
                @unlink($old);
            }
            file_put_contents($certFile, $cert . "\n");
            file_put_contents($keyFile, $key . "\n");
            @chmod($keyFile, 0600);
        }
        return ['cert' => $certFile, 'ssl_key' => $keyFile];
    }

    /** OAuth2 client credentials, cached until shortly before it expires. */
    protected static function token(array $s)
    {
        $cacheKey = 'pispi_token_' . substr(hash('sha256', $s['tokenUrl'] . '|' . $s['clientId'] . '|' . $s['clientSecret']), 0, 24);
        $t = Cache::get($cacheKey);
        if ($t) {
            return $t;
        }
        $r = Http::asForm()->acceptJson()->timeout(20)->withOptions(self::tls($s))->post(trim((string)$s['tokenUrl']), [
            'grant_type' => 'client_credentials',
            'client_id' => trim((string)$s['clientId']),
            'client_secret' => trim((string)$s['clientSecret']),
            'scope' => self::SCOPE,
        ]);
        $t = (string)($r->json('access_token') ?? '');
        if (!$r->successful() || $t === '') {
            Log::warning('PI-SPI token refused', ['http' => $r->status(), 'error' => $r->json('error')]);
            return null;
        }
        Cache::put($cacheKey, $t, max(60, (int)($r->json('expires_in') ?? 3600) - 120));
        return $t;
    }

    /** Calls the API Business; returns [http status, json body] (body null on a network error). */
    protected static function call(array $s, $method, $path, array $body = null)
    {
        $token = self::token($s);
        if (!$token) {
            return [0, null];
        }
        try {
            $req = Http::acceptJson()->timeout(30)->withOptions(self::tls($s))->withToken($token)
                ->withHeaders(['x-api-key' => trim((string)$s['apiKey'])]);
            $r = $method === 'POST' ? $req->post(self::apiUrl($s) . $path, $body ?? []) : $req->get(self::apiUrl($s) . $path);
        } catch (\Throwable $e) {
            Log::warning('PI-SPI unreachable: ' . $e->getMessage(), ['path' => $path]);
            return [0, null];
        }
        $json = $r->json();
        if (!$r->successful()) {
            // Never log the credentials: the HTTP status and PI-SPI's error only.
            Log::warning('PI-SPI API refused', ['http' => $r->status(), 'path' => $path,
                'error' => is_array($json) ? array_intersect_key($json, array_flip(['code', 'title', 'detail', 'message', 'statutRaison'])) : null]);
        }
        if (is_array($json) && isset($json['data']) && is_array($json['data']) && !isset($json['statut'])) {
            $json = $json['data'];
        }
        return [$r->status(), is_array($json) ? $json : null];
    }

    /**
     * Puts the payment in the session and returns our payment page. Same
     * arguments as Sebpay::start() without the country.
     */
    public static function start($flow, $amount, $currency, $reason, array $customer, $successUrl, $failUrl)
    {
        $s = self::settings();
        if (!self::enabled($s)) {
            throw new \RuntimeException(trans('lang.pispi_unavailable'));
        }
        if (strtoupper((string)$currency) !== self::CURRENCY) {
            throw new \RuntimeException(trans('lang.pispi_wrong_currency', ['currency' => strtoupper((string)$currency)]));
        }
        $amount = (int)round((float)$amount);   // XOF has no decimals
        if ($amount <= 0) {
            throw new \RuntimeException(trans('lang.mycoolpay_wrong_amount'));
        }
        Session::put(self::SESSION, [
            'flow' => $flow, 'amount' => $amount, 'currency' => self::CURRENCY, 'reason' => mb_substr((string)$reason, 0, 100),
            'email' => (string)($customer['email'] ?? ''), 'successUrl' => $successUrl, 'failUrl' => $failUrl,
            'txId' => null, 'requests' => 0, 'sentAt' => null,
            'confirmToken' => bin2hex(random_bytes(16)), 'confirmed' => false, 'startedAt' => time(),
        ]);
        Session::save();
        return route('pispi.pay');
    }

    public static function pending()
    {
        $p = Session::get(self::SESSION);
        return is_array($p) ? $p : null;
    }

    protected static function txId()
    {
        return 'SPD' . strtoupper(bin2hex(random_bytes(10)));   // 23 characters, unique (DU03 otherwise)
    }

    /**
     * Sends the payment request to the customer's alias.
     * Returns ['ok' => bool, 'message' => ..., 'payeurNom' => ...].
     */
    public static function request($alias)
    {
        $p = self::pending();
        if (!$p || !empty($p['confirmed'])) {
            return ['ok' => false, 'message' => trans('lang.pispi_no_pending')];
        }
        $alias = strtolower(trim((string)$alias));
        if (!preg_match(self::ALIAS, $alias)) {
            return ['ok' => false, 'message' => trans('lang.pispi_alias_invalid')];
        }
        if ((int)$p['requests'] >= self::MAX_REQUESTS) {
            return ['ok' => false, 'message' => trans('lang.pispi_too_many')];
        }
        // A request that may still be paid is never replaced by a second one.
        if (!empty($p['txId']) && time() - (int)$p['sentAt'] < self::PAY_WITHIN + 30) {
            $st = self::poll();
            if (in_array($st['statut'], ['IRREVOCABLE', 'ENVOYE', 'INITIE'], true)) {
                return ['ok' => false, 'message' => trans('lang.pispi_already_sent')];
            }
            $p = self::pending();
        }
        $s = self::settings();
        if (!self::enabled($s)) {
            return ['ok' => false, 'message' => trans('lang.pispi_unavailable')];
        }
        $txId = self::txId();
        $body = [
            'txId' => $txId,
            'confirmation' => false,
            'categorie' => '521',
            'payeurAlias' => $alias,
            'payeAlias' => strtolower(trim((string)$s['payeAlias'])),
            'montant' => (int)$p['amount'],
            'dateLimitePaiement' => gmdate('Y-m-d\TH:i:s.000\Z', time() + self::PAY_WITHIN),
            'motif' => mb_substr($p['reason'] ?: 'Spideli', 0, 140),
        ];
        $p['requests'] = (int)$p['requests'] + 1;
        $p['txId'] = $txId;
        $p['sentAt'] = time();
        $p['statut'] = null;
        Session::put(self::SESSION, $p);
        Session::save();
        [$http, $res] = self::call($s, 'POST', '/demandes-paiements', $body);
        $statut = strtoupper((string)($res['statut'] ?? ''));
        self::record($txId, ['flow' => $p['flow'], 'amount' => (int)$p['amount'], 'currency' => self::CURRENCY,
            'status' => $statut !== '' ? $statut : 'ERROR_' . $http, 'reason' => (string)($res['statutRaison'] ?? ''),
            'endToEndId' => (string)($res['endToEndId'] ?? ''), 'userEmail' => $p['email'], 'source' => 'website', 'sandbox' => self::sandbox($s)]);
        if ($statut === 'ENVOYE' || $statut === 'INITIE' || $statut === 'IRREVOCABLE') {
            return ['ok' => true, 'payeurNom' => (string)($res['payeurNom'] ?? $res['payeur']['nom'] ?? '')];
        }
        $p['txId'] = null;
        Session::put(self::SESSION, $p);
        Session::save();
        $why = (string)($res['statutRaison'] ?? '');
        return ['ok' => false, 'message' => $why === 'BE23' ? trans('lang.pispi_alias_unknown') : trans('lang.pispi_request_failed')];
    }

    /**
     * The current request's status, read from PI-SPI. When it is IRREVOCABLE
     * for our amount, the payment is confirmed and 'redirect' leads to the
     * checkout's success page.
     */
    public static function poll()
    {
        $p = self::pending();
        if (!$p || empty($p['txId'])) {
            return ['statut' => 'NONE'];
        }
        if (!empty($p['confirmed'])) {
            return ['statut' => 'IRREVOCABLE', 'redirect' => self::successRedirect($p)];
        }
        $s = self::settings();
        [$http, $res] = $s ? self::call($s, 'GET', '/demandes-paiements/' . rawurlencode($p['txId'])) : [0, null];
        $statut = strtoupper((string)($res['statut'] ?? ''));
        if ($statut === 'IRREVOCABLE') {
            $sameTx = !isset($res['txId']) || (string)$res['txId'] === (string)$p['txId'];
            $sameAmount = abs((float)($res['montant'] ?? -1) - (float)$p['amount']) < 0.01;
            if (!$sameTx || !$sameAmount) {
                Log::warning('PI-SPI: a paid request did not match its checkout', ['txId' => $p['txId'], 'flow' => $p['flow']]);
                self::record($p['txId'], ['status' => 'IRREVOCABLE_MISMATCH']);
                return ['statut' => 'REJETE', 'message' => trans('lang.mycoolpay_mismatch')];
            }
            $p['confirmed'] = true;
            Session::put(self::SESSION, $p);
            Session::save();
            self::record($p['txId'], ['status' => 'IRREVOCABLE', 'confirmedBy' => 'website_status', 'endToEndId' => (string)($res['endToEndId'] ?? '')]);
            return ['statut' => 'IRREVOCABLE', 'redirect' => self::successRedirect($p)];
        }
        if ($statut === 'REJETE' || $statut === 'ANNULE') {
            self::record($p['txId'], ['status' => $statut, 'reason' => (string)($res['statutRaison'] ?? '')]);
            return ['statut' => 'REJETE', 'message' => trans('lang.pispi_rejected')];
        }
        // ENVOYE / INITIE, or PI-SPI not reachable this time: still waiting.
        return ['statut' => $statut !== '' ? $statut : 'ENVOYE',
            'expired' => time() - (int)$p['sentAt'] > self::PAY_WITHIN + 60];
    }

    protected static function successRedirect(array $p)
    {
        $url = $p['successUrl'];
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'pispi_token=' . $p['confirmToken'];
    }

    /** The customer gives up: back to the checkout (never while a request may still be paid). */
    public static function cancel()
    {
        $p = self::pending();
        $fail = $p['failUrl'] ?? '/';
        if ($p && !empty($p['txId']) && empty($p['confirmed'])) {
            $st = self::poll();
            if ($st['statut'] === 'IRREVOCABLE') {
                return $st['redirect'];
            }
            if (in_array($st['statut'], ['ENVOYE', 'INITIE'], true) && empty($st['expired'])) {
                return null;   // still payable: stay on the page
            }
        }
        if ($p && empty($p['confirmed'])) {
            Session::forget(self::SESSION);
            Session::save();
        }
        return $fail;
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
        $p['reference'] = $p['txId'];
        return $p;
    }

    /* --------------------------------------- server-side Firestore (a trace) */

    protected static function accessToken()
    {
        return Cache::remember('pispi_sa_token', 3000, function () {
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

    public static function record($txId, array $fields)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', (string)$txId)) {
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
                . '/databases/(default)/documents/pispi_transactions/' . $txId . '?' . $mask, ['fields' => $enc]);
        } catch (\Throwable $e) {
            Log::warning('PI-SPI transaction not recorded: ' . $e->getMessage());
        }
    }
}
