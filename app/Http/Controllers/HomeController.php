<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    /**
     * The home views, keyed by the service type the chosen section carries.
     *
     * Every section in Firestore holds a `serviceType`, and every value it can
     * hold appears here.
     */
    private const HOME_VIEWS = [
        'Parcel Delivery Service'      => 'home_page.parcel_home',
        'Rental Service'               => 'home_page.rental_home',
        'Ecommerce Service'            => 'home_page.ecommerce_home',
        'Multivendor Delivery Service' => 'home_page.multivendor_home',
        'Cab Service'                  => 'home_page.cab_home',
        'On Demand Service'            => 'home_page.ondemand_home',

        /*
         * Document 2's services, listed so the grouped section list matches
         * Document 1 page 9. Nothing is built behind them in any panel, so
         * they share a screen that says so. Give one of these its own view
         * here when the feature lands.
         */
        'Tontine Service'              => 'home_page.coming_soon_home',
        'Loan Service'                 => 'home_page.coming_soon_home',
        'Investment Service'           => 'home_page.coming_soon_home',
        'AI Assistant Service'         => 'home_page.coming_soon_home',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        /*
         * `set-location` and `storeServiceFile` are both on the exempt list
         * in Controller, so this guards the home route and nothing else.
         *
         * The stock condition was `!section_id && !address_name`, so it only
         * fired when BOTH were missing. A visitor holding one of them reached
         * index(), fell off the end of its if-chain and was served a
         * zero-byte body - a blank white page.
         */
        self::requireLocation();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $serviceType = $_COOKIE['service_type'] ?? '';

        /*
         * A service type with no screen of its own.
         *
         * This used to redirect to set-location, which LOOPS: the customer
         * chooses the service again, arrives here again, and is sent back
         * again with no way out but picking something else. That was harmless
         * while every type had a screen; it stops being harmless the moment
         * an admin adds a type, which they can now do.
         *
         * The "coming soon" screen names the service and offers a way back,
         * which is the honest answer to a service that exists but is not
         * built. An empty service_type still goes to set-location - that is
         * someone with no service chosen at all, not an unknown one.
         */
        if ($serviceType === '') {
            return redirect()->route('set-location');
        }

        if (!isset(self::HOME_VIEWS[$serviceType])) {
            return view('home_page.coming_soon_home');
        }

        return view(self::HOME_VIEWS[$serviceType]);
    }

    public function setLocation()
    {
        return view('layer');
    }

    public function storeServiceFile(Request $request){
		if(!empty($request->serviceJson) && !Storage::disk('local')->has('firebase/credentials.json')){
			Storage::disk('local')->put('firebase/credentials.json',file_get_contents(base64_decode($request->serviceJson)));
		}
	}
}
