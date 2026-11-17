<?php

namespace App\Http\Controllers;

use App\Services\MyPVit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * #92: the pages behind the links registered in the PVit merchant space (Urls).
 *  - /mypvit/callback/{token}: PVit's final status (unsigned). Accepted only from
 *    PVit's servers, confirmed with PVit's status API, kept in
 *    mypvit_transactions/{our reference}. The answer echoes transactionId and
 *    code, as PVit requires.
 *  - /mypvit/return/{success|failure}: the customer comes back; the payment is
 *    accepted only from the verified record (waiting a little for the callback).
 *  - Store / provider panels: PVit's return links are fixed (on this site), so a
 *    panel sends its customer through /mypvit/handoff, which remembers the
 *    panel's return page in a cookie and goes on to PVit; the return page then
 *    forwards the customer there. The panel asks /mypvit/status/{reference}.
 */
class MyPVitController extends Controller
{
    const MAX_WAIT_CHECKS = 24;   // about 2 minutes at one check every 5 seconds
    const BACK_COOKIE = 'mypvit_back';

    /** A store / provider return page: http(s) on spideli.com, a subdomain or this host, path /mypvit/return. */
    public static function panelReturnAllowed($url)
    {
        $u = parse_url((string)$url);
        if (!$u || empty($u['host']) || !in_array(strtolower($u['scheme'] ?? ''), ['https', 'http'], true)
            || isset($u['user']) || isset($u['query']) || isset($u['fragment'])) {
            return false;
        }
        $host = strtolower($u['host']);
        $own = strtolower((string)parse_url(url('/'), PHP_URL_HOST));
        $sameSite = $host === $own || $host === 'spideli.com' || substr($host, -strlen('.spideli.com')) === '.spideli.com';
        return $sameSite && preg_match('#/mypvit/return/?$#', (string)($u['path'] ?? '')) === 1;
    }

    /** A PVit payment page: https on mypvit.pro or one of its subdomains. */
    public static function pvitPageAllowed($url)
    {
        $u = parse_url((string)$url);
        $host = strtolower((string)($u['host'] ?? ''));
        return $u && strtolower($u['scheme'] ?? '') === 'https' && !isset($u['user'])
            && ($host === 'mypvit.pro' || substr($host, -strlen('.mypvit.pro')) === '.mypvit.pro');
    }

    /** /mypvit/handoff?to=<PVit page>&back=<panel return page>: remember the panel, go on to PVit. */
    public function handoff(Request $request)
    {
        $to = (string)$request->query('to', '');
        $back = (string)$request->query('back', '');
        if (!self::pvitPageAllowed($to) || !self::panelReturnAllowed($back)) {
            abort(404);
        }
        return redirect()->away($to)->withCookie(cookie(self::BACK_COOKIE, $back, 60, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    /**
     * /mypvit/status/{reference}: what this site knows of one payment, for the
     * store / provider panel that started it (re-checked with PVit until final).
     * The reference is a random 12-hex code only that panel knows.
     */
    public function status($reference)
    {
        $record = MyPVit::readRecord($reference) ?: [];
        $final = !empty($record['verified']) && in_array(strtoupper((string)($record['status'] ?? '')), ['SUCCESS', 'FAILED'], true);
        if (!empty($record['transactionId']) && !$final) {
            $record = self::verify($reference, $record);
        }
        return response()->json([
            'reference' => $reference,
            'status' => strtoupper((string)($record['status'] ?? '')),
            'amount' => isset($record['amount']) && is_numeric($record['amount']) ? $record['amount'] + 0 : null,
            'verified' => !empty($record['verified']),
        ]);
    }

    /** Confirms a record that has PVit's transaction id, with the status API; returns the record. */
    protected static function verify($reference, array $record, array $settings = null)
    {
        $status = MyPVit::checkStatus($record['transactionId'], $settings);
        if (!$status) {
            return $record;
        }
        $ref = (string)($status['merchant_reference_id'] ?? $status['merchantReferenceId'] ?? '');
        if ($ref !== '' && $ref !== $reference) {
            Log::warning('MyPVIT: status for another reference', ['ref' => $reference]);
            return $record;
        }
        $fields = [
            'status' => strtoupper((string)($status['status'] ?? '')),
            'amount' => is_numeric($status['amount'] ?? null) ? $status['amount'] + 0 : null,
            'fees' => is_numeric($status['fees'] ?? null) ? $status['fees'] + 0 : null,
            'operator' => (string)($status['operator'] ?? ''),
            'verified' => true,
            'verifiedAt' => gmdate('c'),
        ];
        MyPVit::record($reference, $fields);
        return $fields + $record;
    }

    public function back($result)
    {
        $p = Session::get(MyPVit::SESSION);
        // A store / provider customer (no payment waiting in this site's session): back to their panel.
        $panelBack = (string)request()->cookie(self::BACK_COOKIE, '');
        if (!$p && $panelBack !== '' && self::panelReturnAllowed($panelBack)) {
            return redirect(rtrim($panelBack, '/') . '/' . $result)->withCookie(cookie()->forget(self::BACK_COOKIE));
        }
        if (!$p) {
            return redirect('/')->with('mycoolpay_error', trans('lang.mypvit_no_pending'));
        }
        $record = MyPVit::readRecord($p['reference']) ?: [];
        // PVit may add its transaction id to the return link: keep it, then confirm.
        $tid = (string)(request()->query('transactionId') ?? request()->query('transaction_id') ?? '');
        if ($tid !== '' && empty($record['transactionId']) && preg_match('/^[A-Za-z0-9_-]{4,60}$/', $tid)) {
            $record['transactionId'] = $tid;
            MyPVit::record($p['reference'], ['transactionId' => $tid]);
        }
        // Ask PVit again until its status is final (SUCCESS or FAILED).
        $final = !empty($record['verified']) && in_array(strtoupper((string)($record['status'] ?? '')), ['SUCCESS', 'FAILED'], true);
        if (!empty($record['transactionId']) && !$final) {
            $record = self::verify($p['reference'], $record);
        }
        if (MyPVit::proves($record, $p)) {
            $p['confirmed'] = true;
            Session::put(MyPVit::SESSION, $p);
            Session::save();
            MyPVit::record($p['reference'], ['confirmedBy' => 'website_return']);
            $url = $p['successUrl'];
            return redirect($url . (strpos($url, '?') === false ? '?' : '&') . 'mypvit_token=' . $p['confirmToken']);
        }
        $state = strtoupper((string)($record['status'] ?? ''));
        if (in_array($state, ['', 'CREATED', 'PENDING', 'AMBIGUOUS'], true) && $result === 'success') {
            $p['checks'] = (int)($p['checks'] ?? 0) + 1;
            Session::put(MyPVit::SESSION, $p);
            Session::save();
            if ($p['checks'] <= self::MAX_WAIT_CHECKS) {
                return response()->view('payment.waiting', ['retryUrl' => route('mypvit.return', 'success'),
                    'text' => trans('lang.mypvit_waiting_text')]);
            }
        }
        $message = $state === 'SUCCESS' ? trans('lang.mycoolpay_mismatch')
            : (in_array($state, ['', 'CREATED', 'PENDING', 'AMBIGUOUS'], true) && $result === 'success' ? trans('lang.mypvit_still_pending') : trans('lang.mycoolpay_not_paid'));
        if ($state === 'SUCCESS') {
            Log::warning('MyPVIT: a successful payment did not match its checkout', ['ref' => $p['reference'], 'flow' => $p['flow']]);
        }
        Session::forget(MyPVit::SESSION);
        Session::save();
        return redirect($p['failUrl'] ?: '/')->with('mycoolpay_error', $message);
    }

    public function callback(Request $request, $token)
    {
        $s = MyPVit::settings() ?: [];
        $saved = (string)($s['callbackToken'] ?? '');
        if ($saved === '' || !hash_equals($saved, (string)$token)) {
            abort(404);
        }
        if (!in_array((string)$request->ip(), MyPVit::CALLBACK_IPS, true)) {
            Log::warning('MyPVIT callback refused: unknown IP', ['ip' => $request->ip()]);
            return response()->json(['message' => 'forbidden'], 403);
        }
        $data = $request->all();
        $tid = (string)($data['transactionId'] ?? '');
        $reference = (string)($data['merchantReferenceId'] ?? '');
        if ($tid !== '' && preg_match('/^[A-Za-z0-9_-]{4,60}$/', $tid) && preg_match('/^[A-Za-z0-9]{6,20}$/', $reference)) {
            $record = [
                'transactionId' => $tid,
                'callbackStatus' => strtoupper((string)($data['status'] ?? '')),
                'callbackAmount' => is_numeric($data['amount'] ?? null) ? $data['amount'] + 0 : null,
                'callbackAt' => gmdate('c'),
            ];
            MyPVit::record($reference, $record);
            // The callback is not signed: its status counts only once PVit's status API confirms it.
            self::verify($reference, $record, $s);
        }
        // PVit's acknowledgement: echo transactionId and code exactly as received.
        return response()->json(['transactionId' => $data['transactionId'] ?? null, 'responseCode' => $data['code'] ?? null], 200);
    }
}
