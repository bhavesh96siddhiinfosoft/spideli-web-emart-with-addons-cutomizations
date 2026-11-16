<?php

namespace App\Http\Controllers;

use App\Services\MyCoolPay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: the pages behind the links set in the MyCoolPay merchant space.
 *  - /mycoolpay/return/{success|cancel|failure}: where MyCoolPay sends the
 *    customer back. A return is not a proof: the status is asked to MyCoolPay
 *    before the checkout's success page may accept the payment.
 *  - /mycoolpay/callback/{token}: MyCoolPay's one notice per transaction,
 *    checked (IP, application, MD5 signature) and kept in
 *    mycoolpay_transactions/{ref}. Answers "OK" or "KO" as MyCoolPay asks.
 */
class MyCoolPayController extends Controller
{
    const MAX_WAIT_CHECKS = 24;   // about 2 minutes at one check every 5 seconds

    public function back($result)
    {
        /* A payment started by the store or provider panel: MyCoolPay copied
         * that panel's return address (spd_back) onto this URL. Forward the
         * customer there, only to a /mycoolpay/return page on this domain. */
        $back = (string)request()->query('spd_back', '');
        if ($back !== '') {
            if (self::panelReturnAllowed($back)) {
                return redirect(rtrim($back, '/') . '/' . $result);
            }
            abort(404);
        }
        $p = Session::get(MyCoolPay::SESSION);
        if (!$p) {
            // Another device or an expired session: nothing to confirm here.
            return redirect('/')->with('mycoolpay_error', trans('lang.mycoolpay_no_pending'));
        }
        $status = MyCoolPay::checkStatus($p['transactionRef']);
        $state = strtoupper((string)($status['transaction_status'] ?? ''));

        if (MyCoolPay::proves($status, $p)) {
            $p['confirmed'] = true;
            Session::put(MyCoolPay::SESSION, $p);
            Session::save();
            MyCoolPay::record($p['transactionRef'], ['status' => 'SUCCESS', 'confirmedBy' => 'website_return',
                'operator' => (string)($status['transaction_operator'] ?? '')]);
            $url = $p['successUrl'];
            return redirect($url . (strpos($url, '?') === false ? '?' : '&') . 'mycoolpay_token=' . $p['confirmToken']);
        }

        // Paid but not yet confirmed by the operator: wait a little, checking again.
        if (in_array($state, ['CREATED', 'PENDING', ''], true) && $result === 'success') {
            $checks = (int)($p['checks'] ?? 0) + 1;
            $p['checks'] = $checks;
            Session::put(MyCoolPay::SESSION, $p);
            Session::save();
            if ($checks <= self::MAX_WAIT_CHECKS) {
                return response()->view('mycoolpay.waiting', ['retryUrl' => route('mycoolpay.return', 'success')]);
            }
        }

        MyCoolPay::record($p['transactionRef'], ['status' => $state !== '' ? $state : strtoupper($result), 'returnedAs' => $result]);
        $message = $state === 'SUCCESS'
            ? trans('lang.mycoolpay_mismatch')            // paid, but not what this checkout asked: never accepted
            : (in_array($state, ['CREATED', 'PENDING', ''], true) ? trans('lang.mycoolpay_still_pending') : trans('lang.mycoolpay_not_paid'));
        if ($state === 'SUCCESS') {
            Log::warning('MyCoolPay: a successful payment did not match its checkout', ['ref' => $p['transactionRef'], 'flow' => $p['flow']]);
        }
        Session::forget(MyCoolPay::SESSION);
        Session::save();
        return redirect($p['failUrl'] ?: '/')->with('mycoolpay_error', $message);
    }

    /** https, on spideli.com or one of its subdomains (or this site's own host), path ending /mycoolpay/return. */
    public static function panelReturnAllowed($url)
    {
        $u = parse_url($url);
        if (!$u || empty($u['host']) || !in_array(strtolower($u['scheme'] ?? ''), ['https', 'http'], true) || isset($u['user']) || isset($u['query']) || isset($u['fragment'])) {
            return false;
        }
        $host = strtolower($u['host']);
        $own = strtolower((string)parse_url(url('/'), PHP_URL_HOST));
        $sameSite = $host === $own || $host === 'spideli.com' || substr($host, -strlen('.spideli.com')) === '.spideli.com';
        return $sameSite && preg_match('#/mycoolpay/return/?$#', (string)($u['path'] ?? '')) === 1;
    }

    public function callback(Request $request, $token)
    {
        $secret = MyCoolPay::secret();
        if (!$secret || $secret['callbackToken'] === '' || !hash_equals($secret['callbackToken'], (string)$token)) {
            abort(404);
        }
        $data = $request->all();
        $settings = MyCoolPay::settings() ?: [];
        $problem = MyCoolPay::verifyCallback($data, (string)$request->ip(), (string)($settings['publicKey'] ?? ''), $secret['privateKey']);
        if ($problem !== null) {
            Log::warning('MyCoolPay callback refused: ' . $problem, ['ip' => $request->ip(), 'ref' => $data['transaction_ref'] ?? null]);
            return response('KO', 200)->header('Content-Type', 'text/plain');
        }
        MyCoolPay::record((string)($data['transaction_ref'] ?? ''), [
            'appTransactionRef' => (string)($data['app_transaction_ref'] ?? ''),
            'operatorTransactionRef' => (string)($data['operator_transaction_ref'] ?? ''),
            'type' => (string)($data['transaction_type'] ?? ''),
            'status' => strtoupper((string)($data['transaction_status'] ?? '')),
            'amount' => is_numeric($data['transaction_amount'] ?? null) ? $data['transaction_amount'] + 0 : null,
            'fees' => is_numeric($data['transaction_fees'] ?? null) ? $data['transaction_fees'] + 0 : null,
            'currency' => (string)($data['transaction_currency'] ?? ''),
            'operator' => (string)($data['transaction_operator'] ?? ''),
            'message' => mb_substr((string)($data['transaction_message'] ?? ''), 0, 300),
            'callbackVerified' => true,
            'callbackAt' => gmdate('c'),
        ]);
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }
}
