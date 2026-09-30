<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * The customer's saved mobile money numbers.
 *
 * Shape is fixed by the customer app (APP-SPEC-CUSTOMER-APP.md section 7) and
 * confirmed against a live record on 30 September:
 *
 *     users/{uid}.savedPaymentMethods: [
 *         { id, type: "mobile_money" | "wave", operator, number,
 *           label?, isDefault, regionId? }
 *     ]
 *
 * WHAT THIS SCREEN DOES NOT DO: anything at checkout. The app prefills a saved
 * number because the app collects the number itself. EVERY payment method in
 * this panel is a hosted redirect or an email-and-name form - Orange Money
 * hands off to Orange's own page, Paystack returns an authorization url,
 * Xendit, Midtrans and PayFast redirect - so there is no number field here to
 * prefill. This screen stores the numbers; the app is what reads them.
 *
 * NO CARD IS EVER STORED. That needs gateway tokenisation, which nobody has
 * chosen a gateway for.
 *
 * Read and written in the browser like every other record here, so this
 * controller only returns the view.
 */
class PaymentMethodController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
        $this->middleware('auth');
    }

    public function index()
    {
        return view('users.payment_methods')->with('id', Auth::id());
    }
}
