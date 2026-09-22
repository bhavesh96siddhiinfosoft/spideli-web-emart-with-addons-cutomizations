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
}
