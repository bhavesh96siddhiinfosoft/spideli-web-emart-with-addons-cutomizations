<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        self::requireLocation();
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('favourites.favouritesVendor');
    }

    public function favProduct()
    {
        return view('favourites.favouritesProduct');
    }

    public function favProvider()
    {
        return view('favourites.favouritesProvider');
    }

    public function favService()
    {
        return view('favourites.favouriteService');
    }
}