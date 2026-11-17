<?php

namespace App\Http\Controllers;

use App\Services\Sebpay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: Sebpay's return and callback pages, per country (/sebpay/{cm|gn}/...).
 *  - /sebpay/{country}/return: the customer comes back (?checkout_session_id=);
 *    the session is asked to Sebpay server-to-server and accepted only when
 *    "approved" for the same reference, amount and currency.
 *  - /sebpay/{country}/callback/{token}: Sebpay's final status, accepted only
 *    with a valid X-SebPay-Signature, kept in sebpay_transactions/{reference}.
 */
class SebpayController extends Controller
{
    const MAX_WAIT_CHECKS = 24;   // about 2 minutes at one check every 5 seconds

    public function back($country)
    {
        $p = Session::get(Sebpay::SESSION);
        if (!$p || ($p['country'] ?? '') !== $country) {
            return redirect('/')->with('mycoolpay_error', trans('lang.sebpay_no_pending'));
        }
        $session = Sebpay::retrieve($country, $p['sessionId']);
        if (Sebpay::proves($session, $p)) {
            $p['confirmed'] = true;
            Session::put(Sebpay::SESSION, $p);
            Session::save();
            Sebpay::record($p['reference'], ['status' => 'approved', 'confirmedBy' => 'website_return',
                'transactionId' => (string)($session['transaction_id'] ?? '')]);
            $url = $p['successUrl'];
            return redirect($url . (strpos($url, '?') === false ? '?' : '&') . 'sebpay_token=' . $p['confirmToken']);
        }
        $tx = strtolower((string)($session['transaction_status'] ?? ''));
        $st = strtolower((string)($session['status'] ?? ''));
        $pending = !$session || ($tx === '' || $tx === 'pending') && !in_array($st, ['expired', 'canceled'], true);
        if ($pending) {
            $p['checks'] = (int)($p['checks'] ?? 0) + 1;
            Session::put(Sebpay::SESSION, $p);
            Session::save();
            if ($p['checks'] <= self::MAX_WAIT_CHECKS) {
                return response()->view('payment.waiting', ['retryUrl' => route('sebpay.return', $country), 'text' => trans('lang.sebpay_waiting_text')]);
            }
        }
        $message = $tx === 'approved' ? trans('lang.mycoolpay_mismatch') : ($pending ? trans('lang.mypvit_still_pending') : trans('lang.mycoolpay_not_paid'));
        if ($tx === 'approved') {
            Log::warning('Sebpay: an approved payment did not match its checkout', ['ref' => $p['reference'], 'flow' => $p['flow']]);
        }
        Sebpay::record($p['reference'], ['status' => $tx !== '' ? $tx : ($st ?: 'unknown'), 'returned' => true]);
        Session::forget(Sebpay::SESSION);
        Session::save();
        return redirect($p['failUrl'] ?: '/')->with('mycoolpay_error', $message);
    }

    public function callback(Request $request, $country, $token)
    {
        $s = Sebpay::settings($country) ?: [];
        $saved = (string)($s['callbackToken'] ?? '');
        if ($saved === '' || !hash_equals($saved, (string)$token)) {
            abort(404);
        }
        $raw = (string)$request->getContent();
        if (!Sebpay::verifySignature($raw, $request->header(Sebpay::SIGNATURE_HEADER), (string)($s['secretKey'] ?? ''))) {
            Log::warning('Sebpay callback refused: bad signature', ['ip' => $request->ip()]);
            return response()->json(['received' => false], 401);
        }
        $data = json_decode($raw, true) ?: [];
        $reference = (string)($data['external_reference'] ?? '');
        if ($reference !== '') {
            Sebpay::record($reference, [
                'status' => strtolower((string)($data['status'] ?? '')),
                'transactionId' => (string)($data['transaction_id'] ?? ''),
                'callbackAmount' => is_numeric($data['amount'] ?? null) ? $data['amount'] + 0 : null,
                'callbackCurrency' => (string)($data['currency'] ?? ''),
                'callbackVerified' => true,
                'callbackAt' => gmdate('c'),
            ]);
        }
        return response()->json(['received' => true], 200);
    }
}
