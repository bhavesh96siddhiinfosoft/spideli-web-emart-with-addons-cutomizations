<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Routes that must work before a location has been set.
     *
     * Keep this list here rather than in the controllers: which pages are
     * public is one decision, and it should be readable in one place.
     *
     * - set-location is where the guard sends people.
     * - storeServiceFile is an ajax endpoint; answering it with a redirect
     *   would hand it an HTML page it cannot use.
     * - The legal and CMS pages, and contact-us, are reachable by anyone.
     *   A first-time visitor must be able to read the terms and reach support
     *   without first choosing a delivery area, and app-store and payment
     *   reviewers check exactly that.
     * - sendContactUsMail and sendMail are how the contact form submits, so
     *   they follow contact-us. A public page whose form bounces is not
     *   public.
     * - changeLang returns the visitor to the page they were on, so it has to
     *   work on the public pages too.
     */
    private const LOCATION_EXEMPT_ROUTES = [
        'set-location',
        'storeServiceFile',
        'terms',
        'privacy',
        'deliveryofsupport',
        'page',
        'contact_us',
        'sendContactUsMail',
        'sendMail',
        'changeLang',
        /* Public parcel tracking: the receiver has no address set and must
         * never be sent to choose one. */
        'parcel_tracking',
    ];

    /**
     * Whether the visitor has a usable location.
     *
     * This means everything the set-location screen collects before it sends
     * anyone on: a section, the service type written alongside it, and an
     * address. That screen gathers all three - it refuses to continue without
     * an address - so requiring all three here cannot bounce a visitor into a
     * redirect loop.
     *
     * Cookies are tested with !empty rather than isset because a cleared
     * cookie is an empty string, not an absent one.
     *
     * The stock guard in every controller was
     *
     *     !isset($_COOKIE['section_id']) && !isset($_COOKIE['address_name'])
     *
     * which only fired when BOTH were missing, so a visitor holding one of
     * them passed the guard with a half-set location.
     */
    public static function hasLocation()
    {
        return !empty($_COOKIE['section_id'])
            && !empty($_COOKIE['section_name'])
            && !empty($_COOKIE['service_type'])
            && !empty($_COOKIE['address_name']);
    }

    /**
     * Send a visitor without a location to the screen that collects one.
     *
     * Called from controller constructors, so it runs before the auth
     * middleware - a logged-out visitor who does have a location still gets
     * sent to login rather than here.
     */
    public static function requireLocation()
    {
        if (in_array(\Route::currentRouteName(), self::LOCATION_EXEMPT_ROUTES, true)) {
            return;
        }

        if (self::hasLocation()) {
            return;
        }

        \Redirect::to('set-location')->send();
    }

    /**
     * Whether the signed-in customer has an APPROVED business account.
     *
     * Settled with the client on 30 September: wholesale is for approved
     * business accounts. The browser applies the same rule for display, but
     * THE CART IS PRICED HERE, so the rule has to hold here too - the browser
     * posts the wholesale fields, and a posted field is whatever the poster
     * says it is.
     *
     * Honest about what this is worth: this panel posts PRICES from the
     * browser as well, so a determined person can already send a low one.
     * This closes the easy door - the wholesale ladder no longer applies
     * simply because the customer asked - and does not make pricing
     * tamper-proof. See APP-SPEC-WEB.md section 19.
     *
     * FAILS CLOSED. No credentials, no network, a malformed answer, a signed
     * out visitor - all mean retail. Withholding a discount from someone
     * entitled to it is a support call; handing it to everyone when Firestore
     * hiccups is the client's margin.
     *
     * CACHED FOR TEN MINUTES in the session, so an approval granted in the
     * admin panel takes effect without the customer signing out, and a cart
     * operation does not cost a Firestore round trip every time.
     */
    public static function isApprovedBusinessCustomer()
    {
        if (!\Auth::check()) {
            return false;
        }

        $cached = session('business_customer_check');

        if (is_array($cached) && isset($cached['at'], $cached['value']) && (time() - $cached['at']) < 600) {
            return (bool) $cached['value'];
        }

        $approved = self::readApprovedBusinessFromFirestore();

        session(['business_customer_check' => ['at' => time(), 'value' => $approved]]);

        return $approved;
    }

    /**
     * The Firestore read behind isApprovedBusinessCustomer(). Separated so the
     * caching above stays readable, and so every failure path returns false in
     * one place.
     */
    private static function readApprovedBusinessFromFirestore()
    {
        try {
            $uid = \Auth::user()->getvendorId();

            if (empty($uid)) {
                return false;
            }

            if (!\Storage::disk('local')->has('firebase/credentials.json')) {
                \Log::warning('business account check skipped: no firebase credentials; wholesale withheld');
                return false;
            }

            $credentialsPath = storage_path('app/firebase/credentials.json');
            $projectId = json_decode(file_get_contents($credentialsPath), true)['project_id'] ?? '';

            if ($projectId === '') {
                return false;
            }

            $client = new \Google_Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/datastore');
            $client->refreshTokenWithAssertion();
            $token = $client->getAccessToken()['access_token'] ?? '';

            if ($token === '') {
                return false;
            }

            $url = 'https://firestore.googleapis.com/v1/projects/' . $projectId
                . '/databases/(default)/documents/users/' . rawurlencode($uid);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200 || $body === false) {
                return false;
            }

            $fields = json_decode($body, true)['fields'] ?? [];

            $accountType = $fields['accountType']['stringValue'] ?? '';
            $profileStatus = $fields['businessProfile']['mapValue']['fields']['status']['stringValue'] ?? '';

            return $accountType === 'business' && $profileStatus === 'approved';
        } catch (\Throwable $e) {
            \Log::warning('business account check failed; wholesale withheld: ' . $e->getMessage());
            return false;
        }
    }
}
