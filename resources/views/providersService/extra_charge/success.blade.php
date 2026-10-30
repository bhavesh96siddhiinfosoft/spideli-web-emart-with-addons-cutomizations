@include('layouts.app')
@include('layouts.header')
@php
    $cityToCountry = file_get_contents(asset('tz-cities-to-countries.json'));
    $cityToCountry = json_decode($cityToCountry, true);
    $countriesJs = array();
    foreach ($cityToCountry as $key => $value) {
        $countriesJs[$key] = $value;
    }
@endphp
<div class="siddhi-checkout">
    <div class="container position-relative">
        <div class="py-5 row">
            <div class="col-md-12 mb-3">
                <div>
                    <div class="siddhi-cart-item mb-3 rounded shadow-sm bg-white overflow-hidden">
                        <div class="siddhi-cart-item-profile bg-white p-3">
                            <div class="card card-default">
                                <?php $authorName = @$cart['cart_order']['authorName']; ?>
                                @if($message = Session::get('success'))
                                    <div class="py-5 linus-coming-soon d-flex justify-content-center align-items-center">
                                        <div class="col-md-6">
                                            <div class="text-center pb-3">
                                                <h1 class="font-weight-bold payment-status"><?php if (@$authorName) {
                                                        echo @strtoupper($authorName) . ",";
                                                    } ?> {{trans('lang.payment_successfull')}}</h1>
                                                <p>Check your booking <a href="{{route('my-bookings')}}"
                                                                         class="font-weight-bold text-decoration-none text-primary">{{trans('lang.my_orders')}}</a>
                                            </div>
                                            <div class="bg-white rounded text-center p-4 shadow-sm">
                                                <h1 class="display-1 mb-4">{{trans('lang.emoji')}}</h1>
                                                <a href="{{route('my-bookings')}}"
                                                   class="btn rounded btn-primary btn-lg btn-block">{{trans('lang.view_booking')}}</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="data-table_processing_order" class="dataTables_processing panel panel-default" style="display: none;">
    {{trans('lang.processing_success')}}
</div>
@include('layouts.footer')
@include('layouts.nav')
@if($message = Session::get('success'))
    <script src="{{ asset('js/geofirestore.js') }}"></script>
    <script type="text/javascript">
        var fcmToken = '';
        var userId = "<?php echo $id; ?>";
        var userDetailsRef = database.collection('users').where('id', "==", userId);
        var firestore = firebase.firestore();
        var geoFirestore = new GeoFirestore(firestore);
            <?php if (@$cart['paymentStatus'] == true && !empty(@$cart['cart_order']['total_pay']) && !empty(@$cart['cart_order']['orderId'])) { ?>
        var id_order = "{{@$cart['cart_order']['orderId']}}";
        var extraCharge = "{{@$cart['cart_order']['total_pay']}}";
        var providerId = '';
        database.collection('provider_orders').where('id', '==', id_order).get().then(async function (snapshots) {
            orderDetail = snapshots.docs[0].data();
            providerId = orderDetail.provider.author;
            finalCheckout();
        })

        /* Point 81: mark paid exactly the charges in the amount charged (a charge
           added meanwhile stays pending). No provider row: the provider is
           credited the charges with the booking when it completes
           (EXTRA-CHARGES-SPEC 5). Then event 14c to the provider and worker. */
        var extraChargesCharged = {!! json_encode(@$cart['cart_order']['charged'] ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
        async function finalCheckout() {
            markExtraChargesPaid(id_order, extraChargesCharged).then(function () {
                return sendExtraChargesPaidNotification(id_order);
            }).then(function () {
                window.location.href = "{{route('my-bookings')}}";
            });
        }
        <?php } else { ?>
            $('.payment-status').html('{{ trans("lang.payment_failed") }}');
        <?php } ?>
    </script>
@endif