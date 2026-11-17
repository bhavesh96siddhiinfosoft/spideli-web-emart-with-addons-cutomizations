<?php

namespace App\Http\Controllers;

use App\Services\FinanceTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Finance F14: the pages the Spideli app opens for a Finance top-up paid with
 * one of the client's gateways (App\Services\FinanceTopup). No sign-in: the
 * top-up id is the app's own finance_topups document, checked on the server.
 *  - GET /finance/topup/{id}         checks it, then starts the gateway;
 *  - GET /finance/topup/{id}/done    the gateway's success page: credits the wallet;
 *  - GET /finance/topup/{id}/failed  the gateway's failure page.
 */
class FinanceTopupController extends Controller
{
    public function start($id)
    {
        try {
            return redirect(FinanceTopup::start($id));
        } catch (\Throwable $e) {
            $message = $e instanceof \RuntimeException ? $e->getMessage() : trans('lang.finance_topup_unavailable');
            if (!$e instanceof \RuntimeException) {
                Log::warning('Finance top-up not started: ' . $e->getMessage(), ['topup' => $id]);
            }
            if ($message !== trans('lang.finance_topup_already_done')) {
                FinanceTopup::fail($id, $message);
            }
            return $this->result($message === trans('lang.finance_topup_already_done'), $message);
        }
    }

    public function done(Request $request, $id)
    {
        try {
            $c = FinanceTopup::load($id);
        } catch (\Throwable $e) {
            return $this->result(false, trans('lang.finance_topup_unknown'));
        }
        if ($c['status'] === 'completed') {
            return $this->result(true, trans('lang.finance_topup_done', ['amount' => $this->money($c)]));
        }
        $paid = FinanceTopup::proof($c['gateway'], $request->query());
        if (!$paid) {
            return $this->result(false, trans('lang.finance_topup_not_confirmed'));
        }
        try {
            FinanceTopup::credit($id, $paid);
        } catch (\Throwable $e) {
            // Paid but not credited: kept for the admin, who can credit it by hand.
            Log::error('Finance top-up paid but not credited', ['topup' => $id, 'error' => $e->getMessage()]);
            FinanceTopup::fail($id, $e->getMessage(), 'paid_not_credited');
            return $this->result(false, trans('lang.finance_topup_credit_failed'));
        }
        return $this->result(true, trans('lang.finance_topup_done', ['amount' => $this->money($c)]));
    }

    public function failed($id)
    {
        $why = (string)session('mycoolpay_error', trans('lang.mycoolpay_not_paid'));
        FinanceTopup::fail($id, $why);
        return $this->result(false, $why);
    }

    protected function money(array $c)
    {
        $major = FinanceTopup::major($c['amount'], $c['currency']);
        return number_format($major, FinanceTopup::decimals($c['currency']), ',', ' ') . ' ' . $c['currency'];
    }

    protected function result($ok, $message)
    {
        return response()->view('payment.finance_topup_result', ['ok' => (bool)$ok, 'message' => $message]);
    }
}
