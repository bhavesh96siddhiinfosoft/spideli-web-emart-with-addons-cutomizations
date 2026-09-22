<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Display a listing of the resource.
 *
 * @param Illuminate\Http\Request $request
 * @return Response
 */
class DeliveryAddressController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
    }

    public function index()
    {
        return view('delivery_address.index');
    }
}