{{--
    Public parcel tracking.

    Reached by scanning the QR code on a parcel receipt. THE PERSON SCANNING IS
    USUALLY NOT THE CUSTOMER - it is the receiver, who has no account, no
    delivery address set and no reason to make one. So this page is deliberately
    standalone: no header, no navigation, no section list, and above all no
    "choose your location" window, which every other page opens at a visitor
    without an address.

    It also loads only what it needs. Someone checking a parcel on their phone
    at the door should not be waiting for the shop.

    WHAT IS DELIBERATELY NOT SHOWN: full addresses and whole phone numbers. The
    link is public to anyone holding it, and a parcel receipt can be
    photographed, forwarded or left in a box. The page shows enough to confirm
    the parcel is the right one and where it has got to - not enough to be worth
    harvesting. See the note in the spec.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ trans('lang.parcel_tracking_title') }} &middot; <?php echo env('APP_NAME'); ?></title>
    <link rel="icon" type="image/png" href="{{ asset('img/spideli-circle.png') }}">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/font-awesome.min.css') }}" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f2f6f9;
            padding-bottom: 40px;
        }

        .track-bar {
            background: #1c1c1c;
            color: #fff;
            padding: 18px 0;
        }

        .track-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            padding: 20px;
            margin-bottom: 16px;
        }

        /* The progress line sits behind the markers and is clipped by the
           first and last of them, so it never pokes out at either end. */
        .track-steps {
            display: flex;
            position: relative;
            margin-top: 22px;
        }

        .track-steps:before {
            content: '';
            position: absolute;
            top: 13px;
            left: 8%;
            right: 8%;
            height: 3px;
            background: #e4e9ee;
        }

        .track-progress {
            position: absolute;
            top: 13px;
            left: 8%;
            height: 3px;
            background: #9bcf1f;
            transition: width .4s ease;
        }

        .track-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .track-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e4e9ee;
            color: #fff;
            margin: 0 auto 8px;
            line-height: 28px;
            font-size: 13px;
        }

        .track-step.done .track-dot {
            background: #9bcf1f;
        }

        .track-step-label {
            font-size: 11px;
            line-height: 1.3;
            color: #7b8794;
        }

        .track-step.done .track-step-label {
            color: #1c1c1c;
            font-weight: 500;
        }

        .track-status-pill {
            display: inline-block;
            background: #eaf5d3;
            color: #4d7c0f;
            border-radius: 20px;
            padding: 6px 16px;
            font-weight: 600;
        }

        .track-status-pill.stopped {
            background: #fde8e8;
            color: #b42318;
        }

        .track-label {
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #7b8794;
        }

        .track-value {
            font-weight: 600;
            word-break: break-word;
        }
    </style>
</head>
<body>

<div class="track-bar">
    <div class="container">
        <strong><?php echo env('APP_NAME'); ?></strong>
        <span class="float-right small">{{ trans('lang.parcel_tracking_title') }}</span>
    </div>
</div>

<div class="container" style="max-width: 640px;">

    <div id="track_loading" class="track-card text-center text-muted">
        {{ trans('lang.please_wait') }}
    </div>

    {{-- Shown when the id matches nothing. A stranger with a bad link needs a
         plain answer, not an empty page. --}}
    <div id="track_missing" class="track-card text-center" style="display:none;">
        <h5 class="font-weight-bold mb-2">{{ trans('lang.parcel_tracking_not_found') }}</h5>
        <p class="text-muted mb-0">{{ trans('lang.parcel_tracking_not_found_text') }}</p>
    </div>

    <div id="track_body" style="display:none;">
        <div class="track-card">
            <span id="track_status" class="track-status-pill"></span>
            <span id="track_status_date" class="text-muted small ml-2"></span>

            <div id="track_steps_wrap">
                <div class="track-steps">
                    <div class="track-progress" id="track_progress" style="width:0;"></div>
                </div>
            </div>
        </div>

        <div class="track-card">
            <h6 class="font-weight-bold mb-3">{{ trans('lang.parcel_tracking_summary') }}</h6>
            <div class="row">
                <div class="col-6 mb-3">
                    <div class="track-label">{{ trans('lang.order_id') }}</div>
                    <div class="track-value" id="track_order_id"></div>
                </div>
                <div class="col-6 mb-3">
                    <div class="track-label">{{ trans('lang.date') }}</div>
                    <div class="track-value" id="track_created"></div>
                </div>
                <div class="col-6">
                    <div class="track-label">{{ trans('lang.sender') }}</div>
                    <div class="track-value" id="track_sender"></div>
                </div>
                <div class="col-6">
                    <div class="track-label">{{ trans('lang.receiver') }}</div>
                    <div class="track-value" id="track_receiver"></div>
                </div>
            </div>
        </div>

        <div class="track-card">
            <h6 class="font-weight-bold mb-3">{{ trans('lang.parcel_tracking_destination') }}</h6>
            <div class="track-value" id="track_destination"></div>
            <div class="text-muted small mt-1" id="track_contact"></div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>
<script src="{{ asset('js/crypto-js.js') }}"></script>
<script src="{{ asset('js/jquery.cookie.js') }}"></script>
<script src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
    var database = firebase.firestore();
    var parcelId = "<?php echo addslashes($id); ?>";

    /* The six steps the customer sees, and which of the order's own statuses
     * put the parcel at each one.
     *
     * The statuses come from the parcel screens: Order Placed, Order Accepted,
     * Driver Pending, Driver Accepted, Order Shipped, In Transit, Order
     * Completed, and the three that stop a parcel.
     *
     * "Driver Pending" sits at Accepted rather than a step of its own - from
     * the receiver's point of view nothing has happened yet, and a step that
     * means "we are looking for someone" is not worth a marker. */
    var TRACK_STEPS = [
        { label: "{{ trans('lang.parcel_step_registered') }}", statuses: ['Order Placed'] },
        { label: "{{ trans('lang.parcel_step_accepted') }}",   statuses: ['Order Accepted', 'Driver Pending'] },
        { label: "{{ trans('lang.parcel_step_picked_up') }}",  statuses: ['Driver Accepted', 'Order Shipped'] },
        { label: "{{ trans('lang.parcel_step_in_transit') }}", statuses: ['In Transit'] },
        { label: "{{ trans('lang.parcel_step_delivered') }}",  statuses: ['Order Completed'] }
    ];

    var STOPPED = ['Order Cancelled', 'Order Rejected', 'Driver Rejected'];

    $(document).ready(function () {
        database.collection('parcel_orders').doc(parcelId).get().then(function (doc) {
            $('#track_loading').hide();

            if (!doc.exists) {
                $('#track_missing').show();
                return;
            }

            render(doc.data());
        }).catch(function (err) {
            console.error('parcel could not be read', err);
            $('#track_loading').hide();
            $('#track_missing').show();
        });
    });

    function render(order) {
        var status = order.status || '';
        var stopped = STOPPED.indexOf(status) !== -1;

        $('#track_status').text(status).toggleClass('stopped', stopped);

        if (order.createdAt && typeof order.createdAt.toDate === 'function') {
            var created = order.createdAt.toDate();
            $('#track_created').text(created.toDateString());
            $('#track_status_date').text(created.toLocaleString());
        }

        $('#track_order_id').text('#' + (order.id || parcelId));
        $('#track_sender').text(nameOf(order.sender));
        $('#track_receiver').text(nameOf(order.receiver));

        /* Town rather than the whole address, and the last digits of the phone
         * rather than the number. Enough for the receiver to recognise their
         * own parcel; not enough to be worth lifting off a photographed
         * receipt. */
        $('#track_destination').text(shortAddress(order.receiver));
        $('#track_contact').text(maskedPhone(order.receiver));

        if (stopped) {
            /* No progress bar for a parcel that is not coming. Showing a
             * half-filled bar beside "Cancelled" reads as though it is still
             * on its way. */
            $('#track_steps_wrap').hide();
        } else {
            drawSteps(status);
        }

        $('#track_body').show();
    }

    function drawSteps(status) {
        var reached = -1;
        TRACK_STEPS.forEach(function (step, index) {
            if (step.statuses.indexOf(status) !== -1) {
                reached = index;
            }
        });

        /* A status nobody mapped leaves the parcel at the first step rather
         * than at none, so the page still reads as "on its way". */
        if (reached === -1) {
            reached = 0;
        }

        var html = '';
        TRACK_STEPS.forEach(function (step, index) {
            var done = index <= reached ? ' done' : '';
            html += '<div class="track-step' + done + '">' +
                '<div class="track-dot">' + (index <= reached ? '&#10003;' : '') + '</div>' +
                '<div class="track-step-label">' + step.label + '</div>' +
                '</div>';
        });

        $('.track-steps').append(html);

        var span = TRACK_STEPS.length > 1 ? (reached / (TRACK_STEPS.length - 1)) : 0;
        $('#track_progress').css('width', (84 * span) + '%');
    }

    function nameOf(party) {
        return (party && party.name) ? party.name : '-';
    }

    /* The last line of the address only - usually the town. */
    function shortAddress(party) {
        if (!party || !party.address) {
            return '-';
        }

        var parts = String(party.address).split(',').map(function (p) { return p.trim(); })
            .filter(function (p) { return p !== ''; });

        return parts.length > 1 ? parts.slice(-2).join(', ') : parts.join(', ');
    }

    function maskedPhone(party) {
        if (!party || !party.phone) {
            return '';
        }

        var digits = String(party.phone).replace(/\s+/g, '');
        return digits.length > 4
            ? '•••• ' + digits.slice(-4)
            : digits;
    }
</script>
</body>
</html>
