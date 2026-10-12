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
</script>
