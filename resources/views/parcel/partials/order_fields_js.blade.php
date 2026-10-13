<script type="text/javascript">
    /* The tracking number given to the parcel being written, for the steps
       that run after the write (the owner push). */
    var lastParcelTrackingNumber = '';

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
    /* PARCEL-MANAGEMENT-FLOW.md 1.4 and 6.2 - the rest of what the APP writes
     * on a new parcel, so a website parcel moves through the same flow:
     *   sendToDriver   false for a company parcel (its owner assigns it) and
     *                  for a scheduled one; true otherwise
     *   driverID       null, as driverId
     *   pickupCode     the RECEIVER CODE - 6 digits the receiver gives the
     *                  driver at delivery (proof of delivery)
     *   receiverName / receiverPhone (national digits) / receiverCountryCode
     *                  flat, for the receiver SMS
     *   smsOptOut, quoteRequested   false
     *   trackingEvents Created, then Waiting drop-off (pickup point) or Paid
     *                  (paid now); parcelStatus = the last one
     * `receiver` is the order's receiver object, its phone "(+237) 6...". */
    function parcelFlowFields(cart, orderId, authorId, receiver, isSchedule, paidNow) {
        var fields = (cart && cart.parcelOrderFields) || {};
        var now = new Date();
        var digits = new Uint32Array(6);
        (window.crypto || window.msCrypto).getRandomValues(digits);
        var code = '';
        for (var i = 0; i < 6; i++) {
            code += String(digits[i] % 10);
        }

        var phone = String((receiver && receiver.phone) || '');
        var m = phone.match(/^\(\s*(\+?\d+)\s*\)\s*(.*)$/);
        var countryCode = m ? (m[1].charAt(0) === '+' ? m[1] : '+' + m[1]) : '';
        var national = (m ? m[2] : phone).replace(/\D/g, '');

        var events = [{ status: 'Created', role: 'customer', by: authorId || '', at: now }];
        if (fields.pickupMethod === 'pickup_point') {
            events.push({ status: 'Waiting drop-off', role: 'customer', by: authorId || '', at: now });
        } else if (paidNow) {
            events.push({ status: 'Paid', role: 'customer', by: authorId || '', at: now });
        }

        return {
            sendToDriver: fields.carrierId ? false : !isSchedule,
            driverID: null,
            pickupCode: code,
            receiverName: (receiver && receiver.name) || '',
            receiverPhone: national,
            receiverCountryCode: countryCode,
            smsOptOut: false,
            quoteRequested: false,
            trackingEvents: events,
            parcelStatus: events[events.length - 1].status
        };
    }

    /* After the parcel is saved, as the app does: the customer now belongs to
     * the order's region too, and a company parcel tells the company's owner
     * (template carrier_parcel_order; {customerName}, {trackingNumber}).
     * Best effort - the order is already saved. */
    async function parcelAfterSave(order, customerName) {
        try {
            if (order.authorID && order.regionId) {
                await database.collection('users').doc(order.authorID).update({
                    regionIds: firebase.firestore.FieldValue.arrayUnion(order.regionId)
                });
            }
        } catch (err) {
            console.error('region could not be added to the customer', err);
        }
        try {
            if (!order.carrierOwnerId) {
                return;
            }
            var owner = await database.collection('users').doc(order.carrierOwnerId).get();
            var token = owner.exists ? (owner.data().fcmToken || '') : '';
            if (!token) {
                return;
            }
            var tpl = await database.collection('dynamic_notification').where('type', '==', 'carrier_parcel_order').limit(1).get();
            var t = tpl.empty ? {} : tpl.docs[0].data();
            var fill = function (text) {
                return String(text || '').replace(/\{customerName\}/gi, customerName || '').replace(/\{trackingNumber\}/gi, order.trackingNumber || order.id);
            };
            await $.ajax({
                type: 'POST', url: "{{ route('parcel_owner_push') }}",
                data: {
                    _token: "{{ csrf_token() }}", fcm: token, orderId: order.id, trackingNumber: order.trackingNumber || '',
                    title: fill(t.subject || 'New parcel order {trackingNumber}'),
                    message: fill(t.message || '{customerName} booked a parcel with your company. Tracking number: {trackingNumber}.')
                }
            });
        } catch (err) {
            console.error('company owner could not be notified', err);
        }
    }

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

        lastParcelTrackingNumber = 'SPD-' + String(now.getFullYear()).slice(-2) + pad(now.getMonth() + 1) + pad(now.getDate()) + '-' + suffix;
        return {
            qrValue: "{{ route('parcel_tracking', ':id') }}".replace(':id', orderId),
            trackingNumber: lastParcelTrackingNumber,
            parcelStatus: 'Created',
            trackingEvents: [{ status: 'Created', role: 'customer', by: authorId || '', at: now }]
        };
    }
</script>
