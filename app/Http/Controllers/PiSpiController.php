<?php

namespace App\Http\Controllers;

use App\Services\PiSpi;
use Illuminate\Http\Request;

/**
 * #92: the PI-SPI payment page (App\Services\PiSpi).
 *  - GET  /pispi/pay      the customer enters their PI-SPI alias;
 *  - POST /pispi/request  sends the payment request to that alias;
 *  - GET  /pispi/status   read by the page every 3 seconds: the status from PI-SPI,
 *                         and the success page once the request is IRREVOCABLE;
 *  - GET  /pispi/cancel   back to the checkout (not while the request may still be paid).
 */
class PiSpiController extends Controller
{
    public function pay()
    {
        $p = PiSpi::pending();
        if (!$p) {
            return redirect('/')->with('mycoolpay_error', trans('lang.pispi_no_pending'));
        }
        if (!empty($p['confirmed'])) {
            return redirect(PiSpi::poll()['redirect']);
        }
        return response()->view('payment.pispi', [
            'amount' => (int)$p['amount'],
            'waiting' => !empty($p['txId']) && time() - (int)$p['sentAt'] < PiSpi::PAY_WITHIN + 60,
        ]);
    }

    public function request(Request $request)
    {
        return response()->json(PiSpi::request((string)$request->input('alias', '')));
    }

    public function status()
    {
        $st = PiSpi::poll();
        if ($st['statut'] === 'NONE' && !PiSpi::pending()) {
            $st['message'] = trans('lang.pispi_no_pending');
        }
        return response()->json($st);
    }

    public function cancel()
    {
        $to = PiSpi::cancel();
        if ($to === null) {
            return redirect()->route('pispi.pay')->with('pispi_notice', trans('lang.pispi_still_payable'));
        }
        return redirect($to);
    }
}
