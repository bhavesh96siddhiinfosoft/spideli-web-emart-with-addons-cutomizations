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
         * The constructor has already sent anyone without a location to
         * set-location, so this only catches a service_type that is set but
         * unrecognised. Returning nothing renders as a blank page, so send
         * them back to choose instead.
         */
        if (!isset(self::HOME_VIEWS[$serviceType])) {
            return redirect()->route('set-location');
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
