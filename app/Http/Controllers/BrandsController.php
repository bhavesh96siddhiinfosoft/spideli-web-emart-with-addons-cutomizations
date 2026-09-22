<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandsController extends Controller
{
    public function __construct()
    {
        self::requireLocation();
    }

    public function index()
    {
        return view('brands.index');
    }
}