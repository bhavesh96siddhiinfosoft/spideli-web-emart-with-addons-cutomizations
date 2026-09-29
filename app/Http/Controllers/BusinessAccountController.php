<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * The customer's own business account application.
 *
 * The customer app has been able to apply since 24 September and the admin
 * panel has been able to approve since 29 September (APP-SPEC-ADMIN.md §18).
 * The website was the only side with no way in at all - a customer who does
 * not use the app could neither apply nor find out what had happened to an
 * application made from it. This screen is that missing piece and nothing
 * more.
 *
 * WHAT THIS DELIBERATELY DOES NOT DO: grant anything. No panel can mark a
 * product business-only, so an approved account carries no price benefit
 * today. Business-only pricing is a separate feature the client has not asked
 * for. See APP-SPEC-WEB.md §16.
 *
 * Like every other screen in this panel the record is read and written in the
 * browser, so this controller only returns the view.
 */
class BusinessAccountController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
        $this->middleware('auth');
    }

    public function index()
    {
        return view('users.business_account')->with('id', Auth::id());
    }
}
