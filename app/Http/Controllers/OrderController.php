<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorUsers;
use Illuminate\Support\Facades\Auth;
use Session;

class OrderController extends Controller
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
        error_reporting(0);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('my_order.my_order');
    }

    public function completedOrders()
    {
        return view('my_order.completed_order');
    }

    public function pendingOrder()
    {
        return view('my_order.pending_order');
    }

    public function cancelledOrder()
    {
        return view('my_order.cancelled_order');
    }

    public function rejectedOrder()
    {
        return view('my_order.rejected_order');
    }

    /**
     * The per-order route, my_order/{id}.
     *
     * This returned view('my_order.edit'), which does not exist - the
     * directory holds only the five list views - so the route answered every
     * request with a 500.
     *
     * There is no order-detail page anywhere in this panel to point it at:
     * the lists render each order inline, and their "view details" link goes
     * to the relevant list rather than to a page of its own. Building one is
     * a feature, not a fix, so the route forwards to the order list. An old
     * or shared link now lands somewhere useful instead of on an error.
     */
    public function edit($id)
    {
        return redirect()->route('my_order');
    }

    public function addCartNote(Request $request)
    {
        $req = $request->all();
        $addnote = $req['addnote'];
        $cart = Session::get('cart', []);
        $cart['order-note'] = $addnote;
        Session::put('cart', $cart);
        Session::save();
        echo json_encode(array('success' => true,));
        exit;
    }

    public function myDinein()
    {
        return view('my_dinein.my_dinein');
    }

    public function dinein()
    {
        return view('my_dinein.dinein');
    }
}