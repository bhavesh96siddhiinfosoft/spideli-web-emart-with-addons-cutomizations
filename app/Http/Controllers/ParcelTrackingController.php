<?php

namespace App\Http\Controllers;

/**
 * Public parcel tracking, reached by scanning the QR code on a receipt.
 *
 * DELIBERATELY NOT ParcelController. That one requires a signed-in customer
 * and a delivery location, and neither is true of the person this page is for:
 * the receiver, who has no account and no reason to make one before finding
 * out where their parcel is.
 *
 * So this controller has no auth middleware and never calls requireLocation().
 * The route name is also on the exempt list in Controller, so that a later
 * change there cannot quietly start redirecting a stranger to "set location".
 */
class ParcelTrackingController extends Controller
{
    /**
     * The parcel is read in the browser, like every other record in this
     * panel, so the id is all this needs to hand over. An id matching nothing
     * is handled by the page rather than here - a stranger with a bad link
     * deserves a sentence, not a 404 with no explanation.
     */
    public function track($id)
    {
        return view('parcel.tracking', ['id' => $id]);
    }

    /**
     * Track by tracking number. The number is looked up server-side and the
     * visitor is sent to the parcel's own page; nothing about a parcel is
     * shown here, so a wrong guess learns nothing but "not found".
     */
    public function lookup(\Illuminate\Http\Request $request)
    {
        $number = strtoupper(trim((string) $request->query('number', '')));
        if ($number === '') {
            return view('parcel.track_lookup', ['number' => '', 'notFound' => false]);
        }

        $found = preg_match('/^[A-Z0-9-]{4,40}$/', $number)
            ? \App\Helpers\FirestoreHelper::queryCollection('parcel_orders', 'trackingNumber', 'EQUAL', $number)
            : [];
        if (is_array($found) && count($found) > 0) {
            $parcel = reset($found);
            $id = $parcel['id'] ?? '';
            if ($id !== '') {
                return redirect()->route('parcel_tracking', $id);
            }
        }

        return view('parcel.track_lookup', ['number' => $number, 'notFound' => true]);
    }
}
