<?php

namespace App\Http\Controllers;

use App\Models\VendorUsers;
use App\Services\ProviderSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * #114: a signed-in client buys a provider's plan (App\Services\ProviderSubscription).
 *  - GET  /provider-subscription/{planId}            the plan, the price and the payment methods;
 *  - POST /provider-subscription/{planId}            pays: by wallet at once, otherwise starts the gateway;
 *  - GET  /provider-subscription/checkout/{id}/done  the gateway's success page: completes the subscription;
 *  - GET  /provider-subscription/checkout/{id}/failed the gateway's failure page.
 * Every checkout is read back for the signed-in client only.
 */
class ProviderSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show($planId)
    {
        try {
            $offer = ProviderSubscription::offer($planId);
            $methods = ProviderSubscription::methods($offer);
            $customer = ProviderSubscription::get('users/' . $this->customerId()) ?: [];
        } catch (\Throwable $e) {
            return $this->result(false, $this->message($e, 'provider_sub_unavailable'));
        }
        return response()->view('provider_subscription.show', [
            'offer' => $offer, 'methods' => $methods,
            'balance' => is_numeric($customer['wallet_amount'] ?? null) ? (float)$customer['wallet_amount'] : 0.0,
            'error' => session('provider_sub_error'),
        ]);
    }

    public function pay(Request $request, $planId)
    {
        $method = (string)$request->input('method', '');
        $customerId = $this->customerId();
        try {
            $c = ProviderSubscription::checkout($planId, $customerId, $method);
        } catch (\Throwable $e) {
            return redirect()->route('provider-subscription.show', $planId)->with('provider_sub_error', $this->message($e, 'provider_sub_unavailable'));
        }
        if ($c['redirect'] !== null) {
            return redirect($c['redirect']);
        }
        try {
            $done = ProviderSubscription::complete($c['checkoutId'], $customerId);
        } catch (\Throwable $e) {
            ProviderSubscription::failCheckout($c['checkoutId'], $customerId, $e->getMessage());
            return redirect()->route('provider-subscription.show', $planId)->with('provider_sub_error', $this->message($e, 'provider_sub_failed'));
        }
        return $this->success($done);
    }

    public function done(Request $request, $id)
    {
        $customerId = $this->customerId();
        try {
            $c = ProviderSubscription::loadCheckout($id, $customerId);
        } catch (\Throwable $e) {
            return $this->result(false, trans('lang.provider_sub_unknown'));
        }
        if (($c['status'] ?? '') === 'completed') {
            return $this->success($c);
        }
        $paid = ProviderSubscription::gatewayProof((string)($c['method'] ?? ''), $request->query());
        if (!$paid) {
            return $this->result(false, trans('lang.provider_sub_not_confirmed'));
        }
        try {
            $done = ProviderSubscription::complete($id, $customerId, $paid);
        } catch (\Throwable $e) {
            // Paid at the gateway but not completed here: kept as paid_not_credited for the admin.
            Log::error('Provider subscription paid but not completed', ['checkout' => $id, 'error' => $e->getMessage()]);
            ProviderSubscription::failCheckout($id, $customerId, $e->getMessage(), 'paid_not_credited', (string)($paid['reference'] ?? ''));
            return $this->result(false, trans('lang.provider_sub_paid_not_done'));
        }
        return $this->success($done);
    }

    public function failed($id)
    {
        $why = (string)session('mycoolpay_error', trans('lang.mycoolpay_not_paid'));
        ProviderSubscription::failCheckout($id, $this->customerId(), $why);
        return $this->result(false, $why);
    }

    /** The signed-in client's Firestore id (users/{id}). */
    protected function customerId()
    {
        $user = VendorUsers::where('email', Auth::user()->email)->first();
        abort_if(!$user || !$user->uuid, 403);
        return $user->uuid;
    }

    protected function message(\Throwable $e, $fallback)
    {
        if (!$e instanceof \RuntimeException) {
            Log::warning('Provider subscription: ' . $e->getMessage());
            return trans('lang.' . $fallback);
        }
        return $e->getMessage();
    }

    protected function success(array $c)
    {
        $plan = is_array($c['plan'] ?? null) ? $c['plan'] : [];
        $expiry = ($c['expiryDate'] ?? null) instanceof \DateTimeInterface ? $c['expiryDate']->format('d M Y') : '';
        return $this->result(true, trans('lang.provider_sub_done', ['plan' => (string)($plan['title'] ?? ''), 'date' => $expiry]));
    }

    protected function result($ok, $message)
    {
        return response()->view('provider_subscription.result', ['ok' => (bool)$ok, 'message' => $message]);
    }
}
