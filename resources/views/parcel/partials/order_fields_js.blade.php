<script type="text/javascript">
    /* Point 41 - the fields the app writes on a parcel order, added by BOTH of
     * this site's write paths: cash / wallet on the checkout page, and online
     * payments on the success page. One definition, so the two cannot drift.
     *
     * `cart` is the server's session cart; parcelOrderFields and
     * priceBreakdown were set there by ParcelController::applyParcelQuote().
     * Only the two things known at the moment of writing are added here: the
     * SMS fee, and the total actually charged. subTotal is untouched - it
     * stays the base price, as in the app. */
    function p41OrderFields(cart, totalPaid, smsCharge) {
        var fields = Object.assign({}, (cart && cart.parcelOrderFields) || {});

        if (cart && cart.priceBreakdown) {
            var breakdown = Object.assign({}, cart.priceBreakdown);
            breakdown.smsCharge = parseFloat(smsCharge) || 0;
            breakdown.total = parseFloat(totalPaid) || 0;
            fields.priceBreakdown = breakdown;
        }

        return fields;
    }

    /* Parcel tracking (Document 1) - the fields the APP writes on every parcel
     * since 24 Sep, so a parcel booked on this site is tracked the same way:
     *   qrValue         the public tracking page (the app has written this
     *                   same URL since 5 Oct, so app and site agree)
     *   trackingNumber  SPD-YYMMDD-XXXXXX, as the app makes it
     *   parcelStatus    'Created' - the app's own step name
     *   trackingEvents  the dated history; the driver / owner apps append to it
     * `at` is a plain date: a server timestamp is not allowed inside an array. */
    function parcelTrackingFields(orderId, authorId) {
        var now = new Date();
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var suffix = '';
        var random = new Uint32Array(6);
        (window.crypto || window.msCrypto).getRandomValues(random);
        for (var i = 0; i < 6; i++) {
            suffix += chars.charAt(random[i] % chars.length);
        }

        return {
            qrValue: "{{ route('parcel_tracking', ':id') }}".replace(':id', orderId),
            trackingNumber: 'SPD-' + String(now.getFullYear()).slice(-2) + pad(now.getMonth() + 1) + pad(now.getDate()) + '-' + suffix,
            parcelStatus: 'Created',
            trackingEvents: [{ status: 'Created', role: 'customer', by: authorId || '', at: now }]
        };
    }
</script>
