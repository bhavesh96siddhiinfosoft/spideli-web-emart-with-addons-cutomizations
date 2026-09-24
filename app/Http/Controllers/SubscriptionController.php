<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Customer subscriptions.
 *
 * A customer buys a plan that lifts the free order-history limit set in
 * settings/OrderHistory. Specified in the admin panel's
 * docs/app-spec-customer-subscription.md.
 *
 * The plans themselves are created in the admin panel and carry
 * `planFor: "customer"`. A plan with no `planFor` is a VENDOR plan - that is
 * the rule the whole separation rests on, so this panel never treats a missing
 * value as a customer plan.
 *
 * Like every other screen here, the data is read from Firestore in the browser;
 * this controller only returns the view.
 */
class SubscriptionController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
        $this->middleware('auth');
    }

    /**
     * The plans a customer can buy.
     */
    public function index()
    {
        return view('subscription.plans');
    }
}
