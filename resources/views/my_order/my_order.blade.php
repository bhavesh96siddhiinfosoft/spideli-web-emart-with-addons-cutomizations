@include('layouts.app')
@include('layouts.header')
@php
    $cityToCountry = file_get_contents(public_path('tz-cities-to-countries.json'));
    $cityToCountry = json_decode($cityToCountry, true);
    $countriesJs = [];
    foreach ($cityToCountry as $key => $value) {
        $countriesJs[$key] = $value;
    }
@endphp
<div class="d-none">
    <div class="bg-primary border-bottom p-3 d-flex align-items-center">
        <a class="toggle togglew toggle-2" href="#"><span></span></a>
        <h4 class="font-weight-bold m-0 text-white">{{ trans('lang.my_orders') }}</h4>
    </div>
</div>
<section class="py-4 siddhi-main-body">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                {{-- Shown only when the free limit actually hid something. --}}
                <div id="order_history_notice" class="alert alert-info d-flex align-items-center" style="display:none;">
                    <span id="order_history_notice_text"></span>
                    <a href="{{ route('subscription.plans') }}" class="btn btn-primary btn-sm ml-auto">{{ trans('lang.order_history_see_all') }}</a>
                </div>
            </div>
            {{-- The period picker. Hidden unless the customer's plan carries
                 full order history - a free customer is capped at the most
                 recent few orders anyway, and letting them step through the
                 whole history a month at a time would undo that cap. --}}
            <div class="col-md-12" id="order_period_bar" style="display:none;">
                <div class="bg-white rounded shadow-sm p-3 mb-3">
                    <div class="d-flex flex-wrap align-items-center">
                        <label class="mb-0 mr-2 font-weight-bold" for="order_period">{{ trans('lang.order_history_period') }}</label>
                        <select id="order_period" class="form-control w-auto mr-2 mb-0">
                            <option value="all">{{ trans('lang.order_history_period_all') }}</option>
                        </select>
                        <div id="order_period_custom" class="d-flex flex-wrap align-items-center" style="display:none;">
                            <input type="date" id="order_period_from" class="form-control w-auto mr-2 mb-0">
                            <span class="mr-2">&ndash;</span>
                            <input type="date" id="order_period_to" class="form-control w-auto mr-2 mb-0">
                            <button type="button" id="order_period_apply" class="btn btn-primary btn-sm">{{ trans('lang.order_history_period_apply') }}</button>
                        </div>
                        <span id="order_period_summary" class="text-muted small ml-auto"></span>
                        {{-- 02#54: printing lives in this bar on purpose. The
                             bar is shown only to a customer whose plan carries
                             full order history, so the client's condition -
                             "only users with an active subscription to the
                             order history should have access" - is satisfied
                             by where the button sits, not by a second check
                             that could drift away from the first. --}}
                        <button type="button" id="order_history_print" class="btn btn-outline-primary btn-sm ml-2">
                            <i class="feather-printer mr-1"></i>{{ trans('lang.order_history_print') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-12 top-nav mb-3">
                <ul class="nav nav-tabsa custom-tabsa border-0 bg-white rounded overflow-hidden shadow-sm p-2 c-t-order" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link border-0 text-dark py-3 active" id="completed-tab" data-toggle="tab" href="#completed" role="tab" aria-controls="completed" aria-selected="true">
                            <i class="feather-check mr-2 text-success mb-0"></i> {{ trans('lang.completed') }}</a>
                    </li>
                    <li class="nav-item border-top" role="presentation">
                        <a class="nav-link border-0 text-dark py-3" id="progress-tab" data-toggle="tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">
                            <i class="feather-clock mr-2 text-warning mb-0"></i> {{ trans('lang.on_progress') }}</a>
                    </li>
                    <li class="nav-item border-top" role="presentation">
                        <a class="nav-link border-0 text-dark py-3" id="canceled-tab" data-toggle="tab" href="#canceled" role="tab" aria-controls="canceled" aria-selected="false">
                            <i class="feather-x-circle mr-2 text-danger mb-0"></i> {{ trans('lang.canceled') }}</a>
                    </li>
                    <li class="nav-item border-top" role="presentation">
                        <a class="nav-link border-0 text-dark py-3" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab" aria-controls="rejected" aria-selected="false">
                            <i class="feather-slash mr-2 text-danger mb-0"></i> {{ trans('lang.rejected') }}</a>
                    </li>
                </ul>
            </div>
            {{-- Shown only on paper. Without it a printout is a list of
                 orders with nothing saying whose they are or what period was
                 asked for. --}}
            <div class="col-md-12 order-print-only" id="order_history_print_header">
                <h4 class="mb-1">{{ trans('lang.order_history_print_title') }}</h4>
                <p class="mb-0 small"><strong>{{ trans('lang.order_history_print_customer') }}:</strong> <span id="print_customer"></span></p>
                <p class="mb-0 small"><strong>{{ trans('lang.order_history_print_period') }}:</strong> <span id="print_period"></span></p>
                <p class="mb-3 small"><strong>{{ trans('lang.order_history_print_generated') }}:</strong> <span id="print_generated"></span></p>
            </div>

            <div class="tab-content col-md-12" id="myTabContent">
                <div class="tab-pane fade show active" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                    <h5 class="order-print-only mt-3">{{ trans('lang.order_history_print_completed') }}</h5>
                    <div class="order-body">
                        <div id="completed_orders"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="progress" role="tabpanel" aria-labelledby="progress-tab">
                    <h5 class="order-print-only mt-3">{{ trans('lang.order_history_print_progress') }}</h5>
                    <div class="order-body">
                        <div id="pending_orders"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="canceled" role="tabpanel" aria-labelledby="canceled-tab">
                    <h5 class="order-print-only mt-3">{{ trans('lang.order_history_print_canceled') }}</h5>
                    <div class="order-body">
                        <div id="canceled_orders"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
                    <h5 class="order-print-only mt-3">{{ trans('lang.order_history_print_rejected') }}</h5>
                    <div class="order-body">
                        <div id="rejected_orders"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 02#54: the printed order history.
     Printing what is ALREADY ON SCREEN, rather than rebuilding the orders
     into a separate document. Every total on an order card is worked out in
     buildHTML*Orders - taxes by scope, discounts, packaging, platform fee,
     tips, per-region currency. Recomputing that for paper would be a second
     implementation of the same sums, and the day the two disagree the
     customer is holding the wrong one.

     All four tabs print, not just the open one: the client asked for the
     orders history for a period, and the period is not a status. All four
     panes are already built and in the page - the tab CSS merely hides three
     of them - so revealing them for print costs nothing and cannot disagree
     with the screen. --}}
<style>
    .order-print-only { display: none; }

    @media print {
        /* Site chrome has no place on a statement. */
        header, nav, footer, .navbar, .main-header, .footer,
        #order_period_bar, #order_history_notice, .top-nav,
        .breadcrumb, .btn, .view-det, .ord-com-btn .btn,
        #overlay, .modal, .osahan-menu, .sticky-bar { display: none !important; }

        .order-print-only { display: block !important; }

        /* Every tab, not only the open one. Bootstrap hides the other three
           with .fade/.active; print needs all four. */
        .tab-content > .tab-pane { display: block !important; opacity: 1 !important; }
        .tab-pane.fade { opacity: 1 !important; }

        /* Cards are for a screen: drop the lift and keep the rules, which
           are what makes a long list readable on paper. */
        .shadow-sm, .shadow { box-shadow: none !important; }
        .bg-white { background: #fff !important; }
        .rounded { border-radius: 0 !important; }
        .p-3 { padding: .4rem 0 !important; }
        .pb-3 { padding-bottom: .4rem !important; }

        /* An order must not be split across two sheets. */
        .order-body > div > .pb-3 { page-break-inside: avoid; }
        h5.order-print-only { page-break-after: avoid; }

        /* Thumbnails waste ink and tell the reader nothing. */
        .order_img { display: none !important; }

        body { background: #fff !important; font-size: 11pt; }
        a { text-decoration: none !important; color: #000 !important; }

        /* A status reads as a word on paper; the colour block does not. */
        .bg-success, .bg-danger, .bg-warning, .bg-info {
            background: none !important;
            color: #000 !important;
            font-weight: 700;
            padding: 0 !important;
        }
    }
</style>

@include('layouts.footer')
@include('layouts.nav')

<script type="text/javascript">
    
    var section_id = getCookie('section_id');
    let userCountry = getCookie('userCountryName');
   
    var append_categories = '';
    var completedorsersref = database.collection('vendor_orders').where("author.id", "==", user_uuid).where('section_id', '==', section_id).orderBy('createdAt', 'desc');
    
    var currentCurrency = '';
    var currencyAtRight = false;
    var decimal_degits = 0;
    var products_info = {};

    var refCurrency = regionCurrencyRef();
    refCurrency.get().then(async function(snapshots) {
        var currencyData = snapshots.docs[0].data();
        currentCurrency = currencyData.symbol;
        currencyAtRight = currencyData.symbolAtRight;
        if (currencyData.decimal_degits) {
            decimal_degits = currencyData.decimal_degits;
        }
    });
    
    var deliveryCharge = 0;
    var deliveryChargeRef = database.collection('settings').doc('DeliveryCharge');
    deliveryChargeRef.get().then(async function(deliveryChargeSnapshots) {
        var deliveryChargeData = deliveryChargeSnapshots.data();
        deliveryCharge = deliveryChargeData.amount;
    });

    var distanceType = 'km';
    var driverRef = database.collection('settings').doc('DriverNearBy');
    driverRef.get().then(async function(snapshot) {
        var driverNearByData = snapshot.data();
        distanceType = driverNearByData.distanceType;
    });

    var taxScope = '';
    var isSelfDeliveryGlobally = false;
    var refGlobal = database.collection('settings').doc("globalSettings");
    refGlobal.get().then(async function(
        settingSnapshots) {
        if (settingSnapshots.data()) {
            var settingData = settingSnapshots.data();
            if (settingData.isSelfDelivery) {
                isSelfDeliveryGlobally = true;
            }
            taxScope = settingData.taxScope;
        }
    });

    var taxSetting = [];
    const scopes = ['delivery', 'order', 'packaging', 'platform', 'product'];
    const taxesByScope = {};
    database.collection('tax').where('country', '==', userCountry).where('enable', '==', true).where('scope', 'in', scopes).where('sectionId', '==', section_id).get().then(snapshot => {
        snapshot.forEach(doc => {
            const tax = doc.data();
            (taxesByScope[tax.scope] ??= []).push(tax);
        });
    });

    var platformCharge = '0';
    let platformFeeSettings = JSON.parse(localStorage.getItem('platformFeeSettings'));
    if (platformFeeSettings && platformFeeSettings.enable) {
        platformCharge = platformFeeSettings.fee;
    }

    var packagingCharge = '0';
    let packagingChargeEnable = localStorage.getItem('packagingChargeEnable') === 'true' || false;

    var place_holder_image = '';
    var ref_placeholder_image = database.collection('settings').doc("placeHolderImage");
    ref_placeholder_image.get().then(async function(snapshots) {
        var placeHolderImage = snapshots.data();
        place_holder_image = placeHolderImage.image;
    });
    
    async function productInfo(id) {
        var doc = await database.collection('vendor_products').doc(id).get();
        products_info[id] = doc.data();
    }
    
    $(document).ready(async function() {

        jQuery("#overlay").show();
        inValidVendors = await getInvaidUserIds();
        getOrders();
        getActiveTab();
        
        $(document).on("click", '.reorder-add-to-cart', async function(event) {
            
            var order_id = $(this).attr('data-id');
            
            var item = [];
            jQuery(".order_" + order_id).each(function() {
                var category_id = jQuery(this).find('.category_id').val();
                var id = jQuery(this).find('.product_id').val();
                var name = jQuery(this).find('.name').val();
                var price = jQuery(this).find('.price').val();
                var image = jQuery(this).find('.image').val();
                var quantity = jQuery(this).find('.quantity').val();
                var extra_price = jQuery(this).find('.extra_price').val();
                var extra = jQuery(this).find('.extra').val();
                var item_price = jQuery(this).find('.item_price').val();
                var taxSetting = JSON.parse(jQuery(this).find('.taxSetting').val());

                var stock_quantity = undefined;
                if (products_info[id] != undefined) {
                    stock_quantity = products_info[id].quantity;
                }
                var variant_info = null;
                if (jQuery(this).find('.variant_info').val()) {
                    variant_info = jQuery(this).find('.variant_info').val()
                    variant_info = JSON.parse(atob(variant_info));
                    if (products_info[id].item_attribute != null) {
                        $.each(products_info[id].item_attribute.variants, function(key, value) {
                            if (value.variant_sku == variant_info.variant_sku) {
                                variant_info.variant_qty = value.variant_quantity;
                                return false;
                            }
                        });
                    }
                    id = id + 'PV' + variant_info.variant_id;
                }
                
                var item_arr = {
                    'id': id,
                    'name': name,
                    'image': image,
                    'price': price,
                    'quantity': quantity,
                    'stock_quantity': stock_quantity,
                    'extra_price': extra_price,
                    'extra': extra,
                    'item_price': item_price,
                    'variant_info': variant_info,
                    'category_id': category_id,
                    'taxSetting': taxSetting,
                }
                item.push(item_arr);
            });

            var vendor_id = jQuery(".restid_" + order_id).val();
            var isSelfDeliveryByVendor = false;
            await database.collection('vendors').doc(vendor_id).get().then(async function(snapshot) {
                if (snapshot.exists) {
                    data = snapshot.data();
                    if (data.hasOwnProperty('isSelfDelivery') && data.isSelfDelivery) {
                        isSelfDeliveryByVendor = true;
                    }
                    if(packagingChargeEnable){
                        packagingCharge = data.packagingCharge ?? '0';
                    }
                }
            });

            var vendor_name = jQuery(".resttitle_" + order_id).val();
            var vendor_location = jQuery(".restlocation_" + order_id).val();
            var vendor_latitude = jQuery(".restlatitude_" + order_id).val();
            var vendor_longitude = jQuery(".restlongitude_" + order_id).val();
            var vendor_image = jQuery(".restphoto_" + order_id).val();

            const payload = {
                _token: '<?php echo csrf_token(); ?>',
                vendor_id,
                vendor_location,
                vendor_name,
                vendor_image,
                vendor_latitude,
                vendor_longitude,
                item,
                deliveryCharge,
                taxSetting,
                decimal_degits,
                distanceType,
                taxScope,
                taxesByScope,
                packagingCharge,
                platformCharge,
                packagingChargeEnable,
                currencyData,
                isSelfDelivery:(isSelfDeliveryByVendor && isSelfDeliveryGlobally) ? true : false
            };
            
            $.ajax({
                type: 'POST',
                url: "<?php echo route('reorder-add-to-cart'); ?>",
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(payload),
                success: function(data) {
                    window.location.href = '{{ route('checkout') }}';
                }
            });
        });
    });

    /* ------------------------------------------------------------------
     * The order-history period.
     *
     * Document 1 asks that a subscriber "choose the month or period for
     * which they want to view their orders". Both ends are inclusive and
     * held as timestamps, so a month and a hand-picked range are the same
     * thing to everything downstream.
     *
     * Changing the period re-renders from the snapshot already in hand -
     * it never re-reads Firestore. The whole history came down in one
     * query to begin with, so narrowing it is free.
     * ------------------------------------------------------------------ */
    var orderSnapshots = null;
    var orderPeriodFrom = null;
    var orderPeriodTo = null;

    /* Only the months the customer actually has orders in, newest first,
     * so no one can pick a month that was always going to be empty. */
    function populateOrderPeriods(snapshots) {
        var seen = {};
        var months = [];
        snapshots.docs.forEach(function (doc) {
            var created = doc.data().createdAt;
            if (!created || typeof created.toDate !== 'function') {
                return;
            }
            var date = created.toDate();
            var key = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2);
            if (!seen[key]) {
                seen[key] = true;
                months.push({
                    key: key,
                    label: date.toLocaleString('en-US', { month: 'long', year: 'numeric' })
                });
            }
        });
        months.sort(function (a, b) { return a.key < b.key ? 1 : -1; });

        var select = $('#order_period');
        months.forEach(function (month) {
            select.append($('<option>').val('month:' + month.key).text(month.label));
        });
        select.append($('<option>').val('custom').text("{{ trans('lang.order_history_period_custom') }}"));
    }

    /* The picker appears only for a customer entitled to the full history.
     * hasFullOrderHistory() fails open, so a customer is never shut out of
     * their own orders because a lookup errored. */
    async function initOrderPeriod(snapshots) {
        if (!(await hasFullOrderHistory())) {
            return;
        }
        populateOrderPeriods(snapshots);
        $('#order_period_bar').show();
    }

    /* Drops orders outside the chosen period. An order with no usable date
     * is KEPT - hiding a customer's order because its timestamp is odd
     * reads as lost data. */
    function withinOrderPeriod(order) {
        if (!orderPeriodFrom && !orderPeriodTo) {
            return true;
        }
        if (!order.createdAt || typeof order.createdAt.toDate !== 'function') {
            return true;
        }
        var date = order.createdAt.toDate();
        if (orderPeriodFrom && date < orderPeriodFrom) { return false; }
        if (orderPeriodTo && date > orderPeriodTo) { return false; }
        return true;
    }

    /* Kept so the printed sheet can say which period it covers. Reading it
     * back out of the summary span would mean unpicking the "Showing: X"
     * wrapper, and that wrapper is translated. */
    var orderPeriodLabel = '';

    function setOrderPeriodSummary(label) {
        orderPeriodLabel = label || '';
        $('#order_period_summary').text(label
            ? "{{ trans('lang.order_history_period_showing') }}".replace(':period', label)
            : '');
    }

    /* ---- 02#54: print the order history for the chosen period ----
     *
     * Asked for across every app; this is the customer website's share. The
     * client's condition - "only users with an active subscription to the
     * order history should have access" - is met by WHERE THE BUTTON LIVES:
     * inside #order_period_bar, which initOrderPeriod() shows only when
     * hasFullOrderHistory() is true. One gate, not two that can drift.
     *
     * The sheet is the page itself under a print stylesheet, so every total
     * on it is the one the customer is already looking at. */
    function orderHistoryPrintName() {
        if (!orderSnapshots || !orderSnapshots.docs.length) {
            return '';
        }

        /* Taken from an order rather than re-read: the author is embedded in
         * every one, and these are this customer's own orders. */
        var author = (orderSnapshots.docs[0].data() || {}).author || {};
        var name = ((author.firstName || '') + ' ' + (author.lastName || '')).trim();
        return name;
    }

    function orderHistoryPrintedOrderCount() {
        /* What is actually on the page, after the period filter and the free
         * allowance - not what came back from Firestore. */
        return $('#completed_orders, #pending_orders, #canceled_orders, #rejected_orders')
            .children().length;
    }

    $(document).on('click', '#order_history_print', function () {
        var name = orderHistoryPrintName();
        $('#print_customer').text(name !== '' ? name : '-');
        $('#print_period').text(orderPeriodLabel !== ''
            ? orderPeriodLabel
            : "{{ trans('lang.order_history_period_all') }}");

        var now = new Date();
        $('#print_generated').text(now.toDateString() + ' ' + now.toLocaleTimeString());

        /* An empty period must print as a sheet that SAYS it is empty. A
         * blank page reads as a failed print, and the customer tries again. */
        $('#order_history_print_empty').remove();
        if (orderHistoryPrintedOrderCount() === 0) {
            $('#order_history_print_header').append(
                $('<p>').attr('id', 'order_history_print_empty')
                        .addClass('order-print-only')
                        .text("{{ trans('lang.order_history_print_none') }}")
            );
        }

        window.print();
    });

    $(document).on('change', '#order_period', function () {
        var value = $(this).val();
        $('#order_period_custom').toggle(value === 'custom');

        if (value === 'custom') {
            /* Nothing changes until Apply - a half-entered range would
             * otherwise blank the list while the customer is still typing. */
            return;
        }

        if (value === 'all') {
            orderPeriodFrom = null;
            orderPeriodTo = null;
            setOrderPeriodSummary('');
        } else {
            var parts = value.replace('month:', '').split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            orderPeriodFrom = new Date(year, month, 1, 0, 0, 0, 0);
            /* Day 0 of the next month is the last day of this one, so a
             * short month or a leap February needs no special case. */
            orderPeriodTo = new Date(year, month + 1, 0, 23, 59, 59, 999);
            setOrderPeriodSummary($(this).find('option:selected').text());
        }
        renderOrders();
    });

    $(document).on('click', '#order_period_apply', function () {
        var from = $('#order_period_from').val();
        var to = $('#order_period_to').val();
        if (!from && !to) {
            alert("{{ trans('lang.order_history_period_pick_dates') }}");
            return;
        }
        /* Either end on its own is allowed: "since March" and "up to March"
         * are both reasonable things to ask for. Both ends are inclusive. */
        orderPeriodFrom = from ? new Date(from + 'T00:00:00') : null;
        orderPeriodTo = to ? new Date(to + 'T23:59:59') : null;
        if (orderPeriodFrom && orderPeriodTo && orderPeriodFrom > orderPeriodTo) {
            alert("{{ trans('lang.order_history_period_bad_range') }}");
            return;
        }
        setOrderPeriodSummary([from, to].filter(Boolean).join(' - '));
        renderOrders();
    });

    function getActiveTab() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('activeTab');
        const newUrl = window.location.href.replace(/[?&]activeTab=[^&]+/, '').replace(/&$/, '').replace(/\?$/, '');
        history.replaceState(null, null, newUrl);
        if (activeTab) {
            const defaultActiveTab = document.querySelector('.tab-pane.fade.show.active');
            const defaultActiveTabClass = document.querySelector('.nav-link.border-0.text-dark.py-3.active');
            if (defaultActiveTab) {
                defaultActiveTab.classList.remove('show', 'active');
                defaultActiveTabClass.classList.remove('show', 'active');
            }
            const tabElement = document.querySelector(`#${activeTab}-tab`);
            if (tabElement) {
                tabElement.classList.add('active');
                const tabContentElement = document.querySelector(`#${activeTab}`);
                if (tabContentElement) {
                    tabContentElement.classList.add('show', 'active');
                }
            }
        }
    }
    
    /* Read once; the period picker re-renders from this. */
    async function getOrders() {
        completedorsersref.get().then(async function(completedorderSnapshots) {
            orderSnapshots = completedorderSnapshots;
            await initOrderPeriod(orderSnapshots);
            await renderOrders();
        })
    }

    async function renderOrders() {
        if (!orderSnapshots) {
            return;
        }
        var orders = orderSnapshots;

        /* The free allowance is worked out ONCE for the whole screen, not per
         * tab - client decision, 28 Sep, replacing the per-tab cap of 24 Sep.
         * A customer without a subscription sees their N most recent orders of
         * any kind; everything older is hidden whichever tab it would sit in.
         *
         * A consequence the client accepted knowingly: a tab can be empty
         * while orders of that kind exist, because the allowance was used up
         * by newer orders in other tabs. */
        await applyFreeOrderAllowance();

        completed_orders = document.getElementById('completed_orders');
        pending_orders = document.getElementById('pending_orders');
        rejected_orders = document.getElementById('rejected_orders');
        canceled_orders = document.getElementById('canceled_orders');
        /* Cleared on every render: the limit may not bite in the
         * period the customer has just picked. */
        $('#order_history_notice').hide();
        completed_orders.innerHTML = '';
        pending_orders.innerHTML = '';
        rejected_orders.innerHTML = '';
        canceled_orders.innerHTML = '';
        completedOrderHtml = await buildHTMLCompletedOrders(orders);
        pendingOrderHtml = await buildHTMLPendingOrders(orders);
        rejectedOrdersHtml = await buildHTMLRejectedOrders(orders);
        canceledOrdersHtml = await buildHTMLCanceledOrders(orders);
        completed_orders.innerHTML = completedOrderHtml;
        pending_orders.innerHTML = pendingOrderHtml;
        rejected_orders.innerHTML = rejectedOrdersHtml;
        canceled_orders.innerHTML = canceledOrdersHtml;
    }

    /* Narrows one tab's orders to its own statuses and applies the free
 * order-history limit to THAT tab.
 *
 * Per tab rather than across the whole history, by the client's decision
 * on 24 Sep: capping the combined set left a customer with older completed
 * orders staring at an empty Completed tab.
 *
 * `orders` arrives newest first, because the query orders by createdAt
 * descending. A customer whose plan carries features.fullOrderHistory, or
 * everyone if the limit is switched off in the admin panel, sees the lot. */
    /* The ids a customer is allowed to see on this render, or null when they
     * may see everything. Filled by applyFreeOrderAllowance() before any tab
     * is built, so all four tabs draw from one decision. */
    var allowedOrderIds = null;

    async function applyFreeOrderAllowance() {
        allowedOrderIds = null;

        /* Fails OPEN. A customer must never lose sight of their own orders
         * because an entitlement lookup errored - that reads as lost data. */
        if (await hasFullOrderHistory()) {
            return;
        }

        var freeLimit = await freeOrderHistoryLimit();
        if (freeLimit <= 0) {
            return;
        }

        /* Newest first, because the query orders by createdAt descending. The
         * period filter runs first so a chosen month is narrowed before the
         * allowance is counted - though only a subscriber can choose one, and
         * a subscriber is already past this point. */
        var visible = [];
        orderSnapshots.docs.forEach(function (doc) {
            var order = doc.data();
            order.id = doc.id;
            if (withinOrderPeriod(order)) {
                visible.push(order);
            }
        });

        if (visible.length <= freeLimit) {
            return;
        }

        allowedOrderIds = {};
        visible.slice(0, freeLimit).forEach(function (order) {
            allowedOrderIds[order.id] = true;
        });

        /* Shown only when the allowance actually hid something - reaching the
         * ninth order, in the client's words, not merely having eight. */
        $('#order_history_notice_text').text(
            "{{ trans('lang.order_history_limited') }}".replace(':count', freeLimit)
        );
        $('#order_history_notice').show();
    }

    /* Narrows one tab to its own statuses, within what the allowance permits. */
    async function limitOrderHistory(orders, statuses) {
        return orders.filter(function (order) {
            if (statuses.indexOf(order.status) === -1 || !withinOrderPeriod(order)) {
                return false;
            }

            return allowedOrderIds === null || allowedOrderIds[order.id] === true;
        });
    }

    async function buildHTMLCompletedOrders(completedorderSnapshots) {
        jQuery("#overlay").show();
        var html = '';
        var alldata = [];
        var number = [];
        if (completedorderSnapshots.docs.length > 0) {
            completedorderSnapshots.docs.forEach((listval) => {
                var datas = listval.data();
                datas.id = listval.id;
                alldata.push(datas);
            });

            /* The free order-history limit, applied PER TAB: this customer
             * sees their most recent N of THIS status, so no tab is left
             * empty while older orders of that kind exist. */
            alldata = await limitOrderHistory(alldata, ['Order Completed']);
            for (const listval of alldata) {

                var val = listval;
                /* An order reads in the currency of the region it was placed
                 * in, not the one the customer happens to be browsing from.
                 * vendor_orders carries its own regionId; getCurrencyForRegion
                 * caches, so this costs one lookup per region, not per order. */
                var orderCurrency = await getCurrencyForRegion(val.regionId);
                
                if (val.status == "Order Completed") {

                    var order_id = val.id;
                    var view_details = "{{ route('completed_order', ':id') }}";
                    view_details = view_details.replace(':id', 'id=' + order_id);
                    var view_contact = "{{ route('contact_us') }}";
                    var view_checkout = "{{ route('checkout') }}";
                    
                    vendorActiveCheck(val.vendorID);

                    let showReorder = false;
                    try {
                        const productSnap = await database
                            .collection('vendor_products')
                            .where("vendorID", "==", val.vendorID)
                            .where("categoryID", "==", val.products[0].category_id)
                            .limit(1)
                            .get();

                        if (!productSnap.empty) {
                            showReorder = productSnap.docs[0].data().publish === true;
                        }
                    } catch (e) {
                        jQuery("#overlay").hide();
                        console.error("Reorder check failed:", e);
                    }  

                    var orderRestaurantImage = '';
                    if (val.vendor.hasOwnProperty('photo') && val.vendor.photo != '') {
                        orderRestaurantImage = val.vendor.photo;
                    } else {
                        orderRestaurantImage = place_holder_image;
                    }
                    html = html + '<div class="pb-3"><div class="p-3 rounded shadow-sm bg-white"><div class="d-flex border-bottom pb-3 m-d-flex"><div class="text-muted mr-3"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '"><img alt="#" src="' + orderRestaurantImage + '" onerror="this.onerror=null;this.src=\'' + place_holder_image +
                        '\'" class="img-fluid order_img rounded"></a></div><div><p class="mb-0 font-weight-bold"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '">' + val.vendor.title + '</a></p><p class="mb-0"><span class="fa fa-map-marker"></span> ' + val.vendor.location + '</p><p>ORDER ' + val.id + '</p><p class="mb-0 small view-det"><a href="' + view_details +
                        '">View Details</a></p></div><div class="ml-auto ord-com-btn"><p class="bg-success text-white py-1 px-2 rounded small mb-1">' + val.status + '</p><p class="small font-weight-bold text-center"><i class="feather-clock"></i> ' + val.createdAt.toDate().toDateString() + '</p></div></div><div class="d-flex pt-3 m-d-flex"><div class="small">';

                    
                    let order_subtotal = 0;
                    let total_discount = 0;
                    let total_tax_amount = 0;
                    let tip_amount = parseFloat(val.tip_amount || 0);
                    let deliveryCharge = parseFloat(val.deliveryCharge || 0);
                    let platformFee = parseFloat(val.platformFee || 0);
                    let packagingCharge = val.packagingChargeEnable ? parseFloat(val.vendor.packagingCharge || 0) : 0;

                    //  Calculate subtotal and product extras
                    for (let i = 0; i < val.products.length; i++) {
                        let product = val.products[i];
                        let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                        let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                        order_subtotal += itemGross;
                    }

                    // Total discounts
                    let order_discount = parseFloat(val.discount || 0);
                    let special_discount = parseFloat(val.specialDiscount?.special_discount || 0);
                        total_discount = order_discount + special_discount;

                    // Calculate item-level taxes (if product-level)
                    if (val.taxScope === "product") {
                        let adminTaxes = taxesByScope['product'] || [];
                        let itemSubtotal = order_subtotal;
                        val.products.forEach(product => {
                            let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                            let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                            let itemDiscount = (itemSubtotal > 0) ? (itemGross / itemSubtotal) * total_discount : 0;
                            let itemTaxable = Math.max(0, itemGross - itemDiscount);
                            let itemTaxes = product.taxSetting || [];
                            itemTaxes.forEach(tax => {
                                let match = adminTaxes.find(t => t.id === tax.id);
                                if (tax.enable && match) {
                                    let taxAmount = 0;
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * itemTaxable;
                                    } else {
                                        taxAmount = tax.tax * product.quantity;
                                    }
                                    total_tax_amount += parseFloat(taxAmount);
                                }
                            });
                        });
                    } 

                    // Order-level taxes (if order-level)
                    if (val.taxScope === "order") {
                        let orderTaxable = Math.max(0, order_subtotal - total_discount);
                        (val.taxSetting || []).forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if (tax.type === "percentage") {
                                    taxAmount = (tax.tax / 100) * orderTaxable;
                                } else {
                                    taxAmount = tax.tax;
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    }

                    // Delivery, packaging, platform taxes
                    let extraCharges = [
                        {amount: deliveryCharge, taxes: val.driverDeliveryTax || []},
                        {amount: packagingCharge, taxes: val.packagingTax || []},
                        {amount: platformFee, taxes: val.platformTax || []},
                    ];

                    extraCharges.forEach(scope => {
                        scope.taxes?.forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if(scope.amount > 0){
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * scope.amount;
                                    } else {
                                        taxAmount = tax.tax;
                                    }
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    });

                    //Final subtotal after discounts
                    order_subtotal = order_subtotal - total_discount;

                    // Final total
                    let order_total = order_subtotal + deliveryCharge + tip_amount + packagingCharge + platformFee + total_tax_amount;

                    order_total_val = formatCurrency(order_total, orderCurrency);

                    for (let i = 0; i < val.products.length; i++) {
                        productInfo(val.products[i]['id']);
                        order_subtotal = order_subtotal + parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productPriceTotal = parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productExtras = 0;
                        if (val.products[i].hasOwnProperty('extras_price') && val.products[i].hasOwnProperty('extras')) {
                            if (val.products[i].extras_price) {
                                productPriceTotal += parseFloat(val.products[i].extras_price);
                                order_subtotal += parseFloat(val.products[i].extras_price);
                                productExtras = val.products[i].extras_price;
                            }
                        }
                        var extras = '';
                        if (val.products[i].hasOwnProperty('extras') && val.products[i].extras != null) {
                            extras = val.products[i].extras;
                        }
                        var size = '';
                        if (val.products[i].hasOwnProperty('size') && val.products[i].size != '') {
                            size = val.products[i].size;
                        }

                        html = html + '<p class="text- font-weight-bold mb-0">' + val.products[i]['name'] + ' x ' + val.products[i]['quantity'] + '</p>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<div class="variant-info">';
                            html = html + '<ul>';
                            $.each(val.products[i]['variant_info']['variant_options'], function(label, value) {
                                html = html + '<li class="variant"><span class="label">' + label + '</span><span class="value">' + value + '</span></li>';
                            });
                            html = html + '</ul>';
                            html = html + '</div>';
                        }
                        
                        html = html + '<div class="order_' + String(order_id) + '">';
                        html = html + '<input type="hidden" class="product_id" value="' + String(val.products[i]['id']) + '">';
                        html = html + '<input type="hidden" class="name" value="' + String(val.products[i]['name']) + '">';
                        html = html + '<input type="hidden" class="image" value="' + String(val.products[i]['photo']) + '">';
                        html = html + '<input type="hidden" class="price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="quantity" value="' + parseFloat(val.products[i]['quantity']) + '">';
                        html = html + '<input type="hidden" class="extra_price" value="' + parseFloat(productExtras) + '">';
                        html = html + '<input type="hidden" class="item_price" value="' + parseFloat(val.products[i]['price']) + '">';
                        if (extras && extras != "null" && extras != null && extras != "") {
                            html = html + '<input type="hidden" class="extra" value="' + extras + '">';
                        }
                        html = html + '<input type="hidden" class="size" value="' + size + '">';
                        html = html + '<input type="hidden" class="taxSetting" value=\'' + JSON.stringify(val.products[i].taxSetting) + '\'>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<input type="hidden" class="variant_info" value="' + btoa(JSON.stringify(val.products[i]['variant_info'])) + '">';
                        }
                        html = html + '<input type="hidden" class="category_id" value="' + val.products[i]['category_id'] + '">';
                        html = html + '</div>';
                    }
                    
                    html = html + '<input type="hidden" class="restid_' + String(order_id) + '" value="' + val.vendor.id + '">';
                    html = html + '<input type="hidden" class="resttitle_' + String(order_id) + '" value="' + val.vendor.title + '">';
                    html = html + '<input type="hidden" class="restlocation_' + String(order_id) + '" value="' + val.vendor.location + '">';
                    html = html + '<input type="hidden" class="restlatitude_' + String(order_id) + '" value="' + val.vendor.latitude + '">';
                    html = html + '<input type="hidden" class="restlongitude_' + String(order_id) + '" value="' +val.vendor.longitude + '">';
                    html = html + '<input type="hidden" class="restphoto_' + String(order_id) + '" value="' + val.vendor.photo + '">';
                    html = html + '<input type="hidden" class="deliveryCharge_' + String(order_id) + '" value="' + deliveryCharge + '">';
                    html = html + '<input type="hidden" class="specialDiscount_' + String(order_id) + '" value="' + special_discount + '">';
                    html = html + '</div><div class="text-muted m-0 ml-auto mr-3 small">Total Payment<br><span class="text-dark font-weight-bold">' + order_total_val + '</span></div> <div class="text-right">';
                    
                    if (showReorder && !inValidVendors.includes(val.vendor.author)) {
                        html = html + '<a href="javascript:void(0);" class="btn btn-primary px-3 reorder-add-to-cart" data-id="' + String(order_id) + '">Reorder</a>';
                    }
                    html = html + '<a href="' + view_contact + '" class="btn btn-outline-primary px-3">Help</a> </div></div></div></div></div></div>';
                }
            }
        }
        if (html == '') {
            html = html + "<p class='text-center font-weight-bold h5 mt-3'>{{ trans('lang.no_results') }}</p>";
        }
        jQuery("#overlay").hide();
        return html;
    }

    async function vendorActiveCheck(vendorId) {
        await database.collection('vendors').where('id', '==', vendorId).get().then(async function(resultCheckVendor) {
            if (resultCheckVendor.docs.length > 0) {
                var vendorData = resultCheckVendor.docs[0].data();
                await database.collection('users').where('id', '==', vendorData.author).get().then(async function(resultCheckUser) {
                    if (resultCheckUser.docs.length > 0) {
                        if (!inValidVendors.includes(vendorData.author)) {
                            var view_vendor_details = "{{ route('vendor', ':id') }}";
                            view_vendor_details = view_vendor_details.replace(':id', vendorId);
                        } else {
                            view_vendor_details = "javascript:void(0)";
                        }
                        $('.check_vendor_' + vendorId).attr('href', view_vendor_details)
                    } else {
                        $('.check_vendor_' + vendorId).attr('href', 'javascript:void(0)')
                    }
                })
            } else {
                $('.check_vendor_' + vendorId).attr('href', 'javascript:void(0)')
            }
        });
    }

    async function buildHTMLPendingOrders(completedorderSnapshots) {
        jQuery("#overlay").show();

        var html = '';
        var alldata = [];
        var number = [];
        
        if (completedorderSnapshots.docs.length > 0) {

            completedorderSnapshots.docs.forEach((listval) => {
                var datas = listval.data();
                datas.id = listval.id;
                alldata.push(datas);
            });

            /* The free order-history limit, applied PER TAB: this customer
             * sees their most recent N of THIS status, so no tab is left
             * empty while older orders of that kind exist. */
            alldata = await limitOrderHistory(alldata, ['Order Placed', 'Order Accepted', 'Driver Pending', 'Order Shipped', 'In Transit']);
            
            for (const listval of alldata) {

                var val = listval;
                /* An order reads in the currency of the region it was placed
                 * in, not the one the customer happens to be browsing from.
                 * vendor_orders carries its own regionId; getCurrencyForRegion
                 * caches, so this costs one lookup per region, not per order. */
                var orderCurrency = await getCurrencyForRegion(val.regionId);
                var order_id = val.id;
                var view_details = "{{ route('pending_order', ':id') }}";
                view_details = view_details.replace(':id', 'id=' + order_id);
                var view_checkout = "{{ route('checkout') }}";
                var view_contact = "{{ route('contact_us') }}";

                vendorActiveCheck(val.vendorID);

                let showReorder = false;
                try {
                    const productSnap = await database
                        .collection('vendor_products')
                        .where("vendorID", "==", val.vendorID)
                        .where("categoryID", "==", val.products[0].category_id)
                        .limit(1)
                        .get();
                    if (!productSnap.empty) {
                        showReorder = productSnap.docs[0].data().publish === true;
                    }
                } catch (e) {
                    jQuery("#overlay").hide();
                    console.error("Reorder check failed:", e);
                }    
                
                if (val.status == "Order Placed" || val.status == "Order Accepted" || val.status == "Driver Pending" || val.status == "Order Shipped" || val.status == "In Transit") {

                    var orderRestaurantImage = '';
                    if (val.vendor.hasOwnProperty('photo') && val.vendor.photo != '') {
                        orderRestaurantImage = val.vendor.photo;
                    } else {
                        orderRestaurantImage = place_holder_image;
                    }

                    html = html + '<div class="pb-3"><div class="p-3 rounded shadow-sm bg-white"><div class="d-flex border-bottom pb-3 m-d-flex"><div class="text-muted mr-3"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '"><img alt="#" src="' + orderRestaurantImage + '" onerror="this.onerror=null;this.src=\'' + place_holder_image +
                        '\'" class="img-fluid order_img rounded"></a></div><div><p class="mb-0 font-weight-bold"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '">' + val.vendor.title + '</a></p><p class="mb-0"><span class="fa fa-map-marker"></span> ' + val.vendor.location + '</p><p>ORDER ' + val.id + '</p><p class="mb-0 small view-det"><a href="' + view_details +
                        '">View Details</a></p></div><div class="ml-auto ord-com-btn"><p class="bg-pending text-white py-1 px-2 rounded small mb-1">' + val.status + '</p><p class="small font-weight-bold text-center"><i class="feather-clock"></i> ' + val.createdAt.toDate().toDateString() + '</p></div></div><div class="d-flex pt-3 m-d-flex"><div class="small">';

                    let order_subtotal = 0;
                    let total_discount = 0;
                    let total_tax_amount = 0;
                    let tip_amount = parseFloat(val.tip_amount || 0);
                    let deliveryCharge = parseFloat(val.deliveryCharge || 0);
                    let platformFee = parseFloat(val.platformFee || 0);
                    let packagingCharge = val.packagingChargeEnable ? parseFloat(val.vendor.packagingCharge || 0) : 0;

                    for (let i = 0; i < val.products.length; i++) {
                        let product = val.products[i];
                        let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                        let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                        order_subtotal += itemGross;
                    }

                    let order_discount = parseFloat(val.discount || 0);
                    let special_discount = parseFloat(val.specialDiscount?.special_discount || 0);
                    total_discount = order_discount + special_discount;

                    // Calculate item-level taxes (if product-level)
                    if (val.taxScope === "product") {
                        let adminTaxes = taxesByScope['product'] || [];
                        let itemSubtotal = order_subtotal;
                        val.products.forEach(product => {
                            let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                            let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                            let itemDiscount = (itemSubtotal > 0) ? (itemGross / itemSubtotal) * total_discount : 0;
                            let itemTaxable = Math.max(0, itemGross - itemDiscount);
                            let itemTaxes = product.taxSetting || [];
                            itemTaxes.forEach(tax => {
                                let match = adminTaxes.find(t => t.id === tax.id);
                                if (tax.enable && match) {
                                    let taxAmount = 0;
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * itemTaxable;
                                    } else {
                                        taxAmount = tax.tax * product.quantity;
                                    }
                                    total_tax_amount += parseFloat(taxAmount);
                                }
                            });
                        });
                    } 

                    // Order-level taxes (if order-level)
                    if (val.taxScope === "order") {
                        let orderTaxable = Math.max(0, order_subtotal - total_discount);
                        (val.taxSetting || []).forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if (tax.type === "percentage") {
                                    taxAmount = (tax.tax / 100) * orderTaxable;
                                } else {
                                    taxAmount = tax.tax;
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    }

                    // Delivery, packaging, platform taxes
                    let extraCharges = [
                        {amount: deliveryCharge, taxes: val.driverDeliveryTax || []},
                        {amount: packagingCharge, taxes: val.packagingTax || []},
                        {amount: platformFee, taxes: val.platformTax || []},
                    ];

                    extraCharges.forEach(scope => {
                        scope.taxes?.forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if(scope.amount > 0){
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * scope.amount;
                                    } else {
                                        taxAmount = tax.tax;
                                    }
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    });

                    order_subtotal = order_subtotal - total_discount;

                    let order_total = order_subtotal + deliveryCharge + tip_amount + packagingCharge + platformFee + total_tax_amount;

                    order_total_val = formatCurrency(order_total, orderCurrency);

                    for (let i = 0; i < val.products.length; i++) {
                        productInfo(val.products[i]['id']);
                        order_subtotal = order_subtotal + parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productPriceTotal = parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productExtras = 0;
                        if (val.products[i].hasOwnProperty('extras_price') && val.products[i].hasOwnProperty('extras')) {
                            if (val.products[i].extras_price) {
                                productPriceTotal += (parseFloat(val.products[i].extras_price) * parseInt(val.products[i]['quantity']));
                                order_subtotal += (parseFloat(val.products[i].extras_price) * parseInt(val.products[i]['quantity']));
                                productExtras = (parseFloat(val.products[i].extras_price) * parseInt(val.products[i]['quantity']));
                            }
                        }
                        var extras = '';
                        if (val.products[i].hasOwnProperty('extras') && val.products[i].extras != '') {
                            extras = val.products[i].extras;
                        }
                        var size = '';
                        if (val.products[i].hasOwnProperty('size') && val.products[i].size != '') {
                            size = val.products[i].size;
                        }
                        html = html + '<p class="text- font-weight-bold mb-0">' + val.products[i]['name'] + ' x ' + val.products[i]['quantity'] + '</p>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<div class="variant-info">';
                            html = html + '<ul>';
                            $.each(val.products[i]['variant_info']['variant_options'], function(label, value) {
                                html = html + '<li class="variant"><span class="label">' + label + '</span><span class="value">' + value + '</span></li>';
                            });
                            html = html + '</ul>';
                            html = html + '</div>';
                        }
                        
                        html = html + '<div class="order_' + String(order_id) + '">';
                        html = html + '<input type="hidden" class="product_id" value="' + String(val.products[i]['id']) + '">';
                        html = html + '<input type="hidden" class="name" value="' + String(val.products[i]['name']) + '">';
                        html = html + '<input type="hidden" class="image" value="' + String(val.products[i]['photo']) + '">';
                        html = html + '<input type="hidden" class="price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="quantity" value="' + parseFloat(val.products[i]['quantity']) + '">';
                        html = html + '<input type="hidden" class="extra_price" value="' + parseFloat(productExtras) + '">';
                        html = html + '<input type="hidden" class="item_price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="extra" value="' + extras + '">';
                        html = html + '<input type="hidden" class="size" value="' + size + '">';
                        html = html + '<input type="hidden" class="taxSetting" value=\'' + JSON.stringify(val.products[i].taxSetting) + '\'>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<input type="hidden" class="variant_info" value="' + btoa(JSON.stringify(val.products[i]['variant_info'])) + '">';
                        }
                        html = html + '<input type="hidden" class="category_id" value="' + val.products[i]['category_id'] + '">';
                        html = html + '</div>';
                    }

                    html = html + '<input type="hidden" class="restid_' + String(order_id) + '" value="' + val.vendor.id + '">';
                    html = html + '<input type="hidden" class="resttitle_' + String(order_id) + '" value="' + val.vendor.title + '">';
                    html = html + '<input type="hidden" class="restlocation_' + String(order_id) + '" value="' + val.vendor.location + '">';
                    html = html + '<input type="hidden" class="restlatitude_' + String(order_id) + '" value="' + val.vendor.latitude + '">';
                    html = html + '<input type="hidden" class="restlongitude_' + String(order_id) + '" value="' +val.vendor.longitude + '">';
                    html = html + '<input type="hidden" class="restphoto_' + String(order_id) + '" value="' + val.vendor.photo + '">';
                    html = html + '<input type="hidden" class="deliveryCharge_' + String(order_id) + '" value="' + deliveryCharge + '">';
                    html = html + '<input type="hidden" class="specialDiscount_' + String(order_id) + '" value="' + special_discount + '">';
                    html = html + '</div><div class="text-muted m-0 ml-auto mr-3 small">Total Payment<br><span class="text-dark font-weight-bold">' + order_total_val + '</span></div> <div class="text-right">';
                    if (showReorder && !inValidVendors.includes(val.vendor.author)) {
                        html = html + '<a href="javascript:void(0);" class="btn btn-primary px-3 reorder-add-to-cart" data-id="' + String(order_id) + '">Reorder</a>';
                    }
                    html = html + '<a href="' + view_contact + '" class="btn btn-outline-primary px-3">Help</a> </div></div></div></div></div></div>';
                }
            }
        }
        if (html == '') {
            html = html + "<p class='text-center font-weight-bold h5 mt-3'>{{ trans('lang.no_results') }}</p>";
        }
        jQuery("#overlay").hide();
        return html;
    }

    async function buildHTMLRejectedOrders(completedorderSnapshots) {
        jQuery("#overlay").show();
        var html = '';
        var alldata = [];
        var number = [];
        if (completedorderSnapshots.docs.length > 0) {

            completedorderSnapshots.docs.forEach((listval) => {
                var datas = listval.data();
                datas.id = listval.id;
                alldata.push(datas);
            });

            /* The free order-history limit, applied PER TAB: this customer
             * sees their most recent N of THIS status, so no tab is left
             * empty while older orders of that kind exist. */
            alldata = await limitOrderHistory(alldata, ['Driver Rejected', 'Order Rejected']);
            
            for (const listval of alldata) {
                var val = listval;
                /* An order reads in the currency of the region it was placed
                 * in, not the one the customer happens to be browsing from.
                 * vendor_orders carries its own regionId; getCurrencyForRegion
                 * caches, so this costs one lookup per region, not per order. */
                var orderCurrency = await getCurrencyForRegion(val.regionId);
                var order_id = val.id;
                var view_details = "{{ route('rejected_order', ':id') }}";
                view_details = view_details.replace(':id', 'id=' + order_id);
                var view_contact = "{{ route('contact_us') }}";
                var view_checkout = "{{ route('checkout') }}";
                vendorActiveCheck(val.vendorID);

                let showReorder = false;
                try {
                    const productSnap = await database
                        .collection('vendor_products')
                        .where("vendorID", "==", val.vendorID)
                        .where("categoryID", "==", val.products[0].category_id)
                        .limit(1)
                        .get();
                    if (!productSnap.empty) {
                        showReorder = productSnap.docs[0].data().publish === true;
                    }
                } catch (e) {
                    jQuery("#overlay").hide();
                    console.error("Reorder check failed:", e);
                }    

                if (val.status == "Driver Rejected" || val.status == "Order Rejected") {               
                    var orderRestaurantImage = '';
                    if (val.vendor.hasOwnProperty('photo') && val.vendor.photo != '') {
                        orderRestaurantImage = val.vendor.photo;
                    } else {
                        orderRestaurantImage = place_holder_image;
                    }
                    html = html + '<div class="pb-3"><div class="p-3 rounded shadow-sm bg-white"><div class="d-flex border-bottom pb-3 m-d-flex"><div class="text-muted mr-3"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '"><img alt="#" src="' + orderRestaurantImage + '" onerror="this.onerror=null;this.src=\'' + place_holder_image +
                        '\'" class="img-fluid order_img rounded"></a></div><div><p class="mb-0 font-weight-bold"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '">' + val.vendor.title + '</a></p><p class="mb-0"><span class="fa fa-map-marker"></span> ' + val.vendor.location + '</p><p>ORDER ' + val.id + '</p><p class="mb-0 small view-det"><a href="' + view_details +
                        '">View Details</a></p></div><div class="ml-auto ord-com-btn"><p class="bg-rejected text-white py-1 px-2 rounded small mb-1">' + val.status + '</p><p class="small font-weight-bold text-center"><i class="feather-clock"></i> ' + val.createdAt.toDate().toDateString() + '</p></div></div><div class="d-flex pt-3 m-d-flex"><div class="small">';
                    
                    let order_subtotal = 0;
                    let total_discount = 0;
                    let total_tax_amount = 0;
                    let tip_amount = parseFloat(val.tip_amount || 0);
                    let deliveryCharge = parseFloat(val.deliveryCharge || 0);
                    let platformFee = parseFloat(val.platformFee || 0);
                    let packagingCharge = val.packagingChargeEnable ? parseFloat(val.vendor.packagingCharge || 0) : 0;

                    //  Calculate subtotal and product extras
                    for (let i = 0; i < val.products.length; i++) {
                        let product = val.products[i];
                        let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                        let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                        order_subtotal += itemGross;
                    }

                    // Total discounts
                    let order_discount = parseFloat(val.discount || 0);
                    let special_discount = parseFloat(val.specialDiscount?.special_discount || 0);
                        total_discount = order_discount + special_discount;

                    // Calculate item-level taxes (if product-level)
                    if (val.taxScope === "product") {
                        let adminTaxes = taxesByScope['product'] || [];
                        let itemSubtotal = order_subtotal;
                        val.products.forEach(product => {
                            let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                            let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                            let itemDiscount = (itemSubtotal > 0) ? (itemGross / itemSubtotal) * total_discount : 0;
                            let itemTaxable = Math.max(0, itemGross - itemDiscount);
                            let itemTaxes = product.taxSetting || [];
                            itemTaxes.forEach(tax => {
                                let match = adminTaxes.find(t => t.id === tax.id);
                                if (tax.enable && match) {
                                    let taxAmount = 0;
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * itemTaxable;
                                    } else {
                                        taxAmount = tax.tax * product.quantity;
                                    }
                                    total_tax_amount += parseFloat(taxAmount);
                                }
                            });
                        });
                    } 

                    // Order-level taxes (if order-level)
                    if (val.taxScope === "order") {
                        let orderTaxable = Math.max(0, order_subtotal - total_discount);
                        (val.taxSetting || []).forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if (tax.type === "percentage") {
                                    taxAmount = (tax.tax / 100) * orderTaxable;
                                } else {
                                    taxAmount = tax.tax;
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    }

                    // Delivery, packaging, platform taxes
                    let extraCharges = [
                        {amount: deliveryCharge, taxes: val.driverDeliveryTax || []},
                        {amount: packagingCharge, taxes: val.packagingTax || []},
                        {amount: platformFee, taxes: val.platformTax || []},
                    ];

                    extraCharges.forEach(scope => {
                        scope.taxes?.forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if(scope.amount > 0){
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * scope.amount;
                                    } else {
                                        taxAmount = tax.tax;
                                    }
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    });
                    //Final subtotal after discounts
                    order_subtotal = order_subtotal - total_discount;

                    // Final total
                    let order_total = order_subtotal + deliveryCharge + tip_amount + packagingCharge + platformFee + total_tax_amount;

                    order_total_val = formatCurrency(order_total, orderCurrency);

                    for (let i = 0; i < val.products.length; i++) {
                        productInfo(val.products[i]['id']);
                        order_subtotal = order_subtotal + parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productPriceTotal = parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productExtras = 0;
                        if (val.products[i].hasOwnProperty('extras_price') && val.products[i].hasOwnProperty('extras')) {
                            if (val.products[i].extras_price) {
                                productPriceTotal += parseFloat(val.products[i].extras_price);
                                order_subtotal += parseFloat(val.products[i].extras_price);
                                productExtras = val.products[i].extras_price;
                            }
                        }
                        var extras = '';
                        if (val.products[i].hasOwnProperty('extras') && val.products[i].extras != '') {
                            extras = val.products[i].extras;
                        }
                        var size = '';
                        if (val.products[i].hasOwnProperty('size') && val.products[i].size != '') {
                            size = val.products[i].size;
                        }
                        html = html + '<p class="text- font-weight-bold mb-0">' + val.products[i]['name'] + ' x ' + val.products[i]['quantity'] + '</p>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<div class="variant-info">';
                            html = html + '<ul>';
                            $.each(val.products[i]['variant_info']['variant_options'], function(label, value) {
                                html = html + '<li class="variant"><span class="label">' + label + '</span><span class="value">' + value + '</span></li>';
                            });
                            html = html + '</ul>';
                            html = html + '</div>';
                        }
                        
                        html = html + '<div class="order_' + String(order_id) + '">';
                        html = html + '<input type="hidden" class="product_id" value="' + String(val.products[i]['id']) + '">';
                        html = html + '<input type="hidden" class="name" value="' + String(val.products[i]['name']) + '">';
                        html = html + '<input type="hidden" class="image" value="' + String(val.products[i]['photo']) + '">';
                        html = html + '<input type="hidden" class="price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="quantity" value="' + parseFloat(val.products[i]['quantity']) + '">';
                        html = html + '<input type="hidden" class="extra_price" value="' + parseFloat(productExtras) + '">';
                        html = html + '<input type="hidden" class="item_price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="extra" value="' + extras + '">';
                        html = html + '<input type="hidden" class="size" value="' + size + '">';
                        html = html + '<input type="hidden" class="taxSetting" value=\'' + JSON.stringify(val.products[i].taxSetting) + '\'>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<input type="hidden" class="variant_info" value="' + btoa(JSON.stringify(val.products[i]['variant_info'])) + '">';
                        }
                        html = html + '<input type="hidden" class="category_id" value="' + val.products[i]['category_id'] + '">';
                        html = html + '</div>';
                    }
                    
                    html = html + '<input type="hidden" class="restid_' + String(order_id) + '" value="' + val.vendor.id + '">';
                    html = html + '<input type="hidden" class="resttitle_' + String(order_id) + '" value="' + val.vendor.title + '">';
                    html = html + '<input type="hidden" class="restlocation_' + String(order_id) + '" value="' + val.vendor.location + '">';
                    html = html + '<input type="hidden" class="restlatitude_' + String(order_id) + '" value="' + val.vendor.latitude + '">';
                    html = html + '<input type="hidden" class="restlongitude_' + String(order_id) + '" value="' +val.vendor.longitude + '">';
                    html = html + '<input type="hidden" class="restphoto_' + String(order_id) + '" value="' + val.vendor.photo + '">';
                    html = html + '<input type="hidden" class="deliveryCharge_' + String(order_id) + '" value="' + deliveryCharge + '">';
                    html = html + '<input type="hidden" class="specialDiscount_' + String(order_id) + '" value="' + special_discount + '">';
                    html = html + '</div><div class="text-muted m-0 ml-auto mr-3 small">Total Payment<br><span class="text-dark font-weight-bold">' + order_total_val + '</span></div> <div class="text-right">';
                    if (showReorder && !inValidVendors.includes(val.vendor.author)) {
                        html = html + '<a href="javascript:void(0);" class="btn btn-primary px-3 reorder-add-to-cart" data-id="' + String(order_id) + '">Reorder</a>';
                    }
                    html = html + '<a href="' + view_contact + '" class="btn btn-outline-primary px-3">Help</a> </div></div></div></div></div></div>';
                }
            }
        }
        if (html == '') {
            html = html + "<p class='text-center font-weight-bold h5 mt-3'>{{ trans('lang.no_results') }}</p>";
        }
        jQuery("#overlay").hide();
        return html;
    }

    async function buildHTMLCanceledOrders(completedorderSnapshots) {
        jQuery("#overlay").show();
        var html = '';
        var alldata = [];
        var number = [];

        if (completedorderSnapshots.docs.length > 0) {
            completedorderSnapshots.docs.forEach((listval) => {
                var datas = listval.data();
                datas.id = listval.id;
                alldata.push(datas);
            });

            /* The free order-history limit, applied PER TAB: this customer
             * sees their most recent N of THIS status, so no tab is left
             * empty while older orders of that kind exist. */
            alldata = await limitOrderHistory(alldata, ['Order Cancelled']);
            for (const listval of alldata) {
                var val = listval;
                /* An order reads in the currency of the region it was placed
                 * in, not the one the customer happens to be browsing from.
                 * vendor_orders carries its own regionId; getCurrencyForRegion
                 * caches, so this costs one lookup per region, not per order. */
                var orderCurrency = await getCurrencyForRegion(val.regionId);
                var order_id = val.id;
                var view_details = "{{ route('cancelled_order', ':id') }}";
                view_details = view_details.replace(':id', 'id=' + order_id);
                var view_contact = "{{ route('contact_us') }}";
                var view_checkout = "{{ route('checkout') }}";
                vendorActiveCheck(val.vendorID);

                let showReorder = false;
                try {
                    const productSnap = await database
                        .collection('vendor_products')
                        .where("vendorID", "==", val.vendorID)
                        .where("categoryID", "==", val.products[0].category_id)
                        .limit(1)
                        .get();
                    if (!productSnap.empty) {
                        showReorder = productSnap.docs[0].data().publish === true;
                    }
                } catch (e) {
                    jQuery("#overlay").hide();
                    console.error("Reorder check failed:", e);
                }    

                if (val.status == "Order Cancelled") {
                    var orderRestaurantImage = '';
                    if (val.vendor.hasOwnProperty('photo') && val.vendor.photo != '') {
                        orderRestaurantImage = val.vendor.photo;
                    } else {
                        orderRestaurantImage = place_holder_image;
                    }
                    html = html + '<div class="pb-3"><div class="p-3 rounded shadow-sm bg-white"><div class="d-flex border-bottom pb-3 m-d-flex"><div class="text-muted mr-3"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '"><img alt="#" src="' + orderRestaurantImage + '" onerror="this.onerror=null;this.src=\'' + place_holder_image +
                        '\'" class="img-fluid order_img rounded"></a></div><div><p class="mb-0 font-weight-bold"><a href="javascript:void(0)" class="text-dark check_vendor_' + val.vendorID + '">' + val.vendor.title + '</a></p><p class="mb-0"><span class="fa fa-map-marker"></span> ' + val.vendor.location + '</p><p>ORDER ' + val.id + '</p><p class="mb-0 small view-det"><a href="' + view_details +
                        '">View Details</a></p></div><div class="ml-auto ord-com-btn"><p class="bg-rejected text-white py-1 px-2 rounded small mb-1">' + val.status + '</p><p class="small font-weight-bold text-center"><i class="feather-clock"></i> ' + val.createdAt.toDate().toDateString() + '</p></div></div><div class="d-flex pt-3 m-d-flex"><div class="small">';
                    
                    let order_subtotal = 0;
                    let total_discount = 0;
                    let total_tax_amount = 0;
                    let tip_amount = parseFloat(val.tip_amount || 0);
                    let deliveryCharge = parseFloat(val.deliveryCharge || 0);
                    let platformFee = parseFloat(val.platformFee || 0);
                    let packagingCharge = val.packagingChargeEnable ? parseFloat(val.vendor.packagingCharge || 0) : 0;

                    //  Calculate subtotal and product extras
                    for (let i = 0; i < val.products.length; i++) {
                        let product = val.products[i];
                        let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                        let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                        order_subtotal += itemGross;
                    }

                    // Total discounts
                    let order_discount = parseFloat(val.discount || 0);
                    let special_discount = parseFloat(val.specialDiscount?.special_discount || 0);
                        total_discount = order_discount + special_discount;

                    // Calculate item-level taxes (if product-level)
                    if (val.taxScope === "product") {
                        let adminTaxes = taxesByScope['product'] || [];
                        let itemSubtotal = order_subtotal;
                        val.products.forEach(product => {
                            let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                            let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                            let itemDiscount = (itemSubtotal > 0) ? (itemGross / itemSubtotal) * total_discount : 0;
                            let itemTaxable = Math.max(0, itemGross - itemDiscount);
                            let itemTaxes = product.taxSetting || [];
                            itemTaxes.forEach(tax => {
                                let match = adminTaxes.find(t => t.id === tax.id);
                                if (tax.enable && match) {
                                    let taxAmount = 0;
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * itemTaxable;
                                    } else {
                                        taxAmount = tax.tax * product.quantity;
                                    }
                                    total_tax_amount += parseFloat(taxAmount);
                                }
                            });
                        });
                    } 

                    // Order-level taxes (if order-level)
                    if (val.taxScope === "order") {
                        let orderTaxable = Math.max(0, order_subtotal - total_discount);
                        (val.taxSetting || []).forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if (tax.type === "percentage") {
                                    taxAmount = (tax.tax / 100) * orderTaxable;
                                } else {
                                    taxAmount = tax.tax;
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    }

                    // Delivery, packaging, platform taxes
                    let extraCharges = [
                        {amount: deliveryCharge, taxes: val.driverDeliveryTax || []},
                        {amount: packagingCharge, taxes: val.packagingTax || []},
                        {amount: platformFee, taxes: val.platformTax || []},
                    ];

                    extraCharges.forEach(scope => {
                        scope.taxes?.forEach(tax => {
                            if (tax.enable) {
                                let taxAmount = 0;
                                if(scope.amount > 0){
                                    if (tax.type === "percentage") {
                                        taxAmount = (tax.tax / 100) * scope.amount;
                                    } else {
                                        taxAmount = tax.tax;
                                    }
                                }
                                total_tax_amount += parseFloat(taxAmount);
                            }
                        });
                    });

                    //Final subtotal after discounts
                    order_subtotal = order_subtotal - total_discount;

                    // Final total
                    let order_total = order_subtotal + deliveryCharge + tip_amount + packagingCharge + platformFee + total_tax_amount;
                
                    order_total_val = formatCurrency(order_total, orderCurrency);

                    for (let i = 0; i < val.products.length; i++) {
                        productInfo(val.products[i]['id']);
                        order_subtotal = order_subtotal + parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productPriceTotal = parseFloat(val.products[i]['price']) * parseFloat(val.products[i]['quantity']);
                        var productExtras = 0;
                        if (val.products[i].hasOwnProperty('extras_price') && val.products[i].hasOwnProperty('extras')) {
                            if (val.products[i].extras_price) {
                                productPriceTotal += parseFloat(val.products[i].extras_price);
                                order_subtotal += parseFloat(val.products[i].extras_price);
                                productExtras = val.products[i].extras_price;
                            }
                        }
                        var extras = '';
                        if (val.products[i].hasOwnProperty('extras') && val.products[i].extras != '') {
                            extras = val.products[i].extras;
                        }
                        var size = '';
                        if (val.products[i].hasOwnProperty('size') && val.products[i].size != '') {
                            size = val.products[i].size;
                        }
                        html = html + '<p class="text- font-weight-bold mb-0">' + val.products[i]['name'] + ' x ' + val.products[i]['quantity'] + '</p>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<div class="variant-info">';
                            html = html + '<ul>';
                            $.each(val.products[i]['variant_info']['variant_options'], function(label, value) {
                                html = html + '<li class="variant"><span class="label">' + label + '</span><span class="value">' + value + '</span></li>';
                            });
                            html = html + '</ul>';
                            html = html + '</div>';
                        }
                        html = html + '<div class="order_' + String(order_id) + '">';
                        html = html + '<input type="hidden" class="product_id" value="' + String(val.products[i]['id']) + '">';
                        html = html + '<input type="hidden" class="name" value="' + String(val.products[i]['name']) + '">';
                        html = html + '<input type="hidden" class="image" value="' + String(val.products[i]['photo']) + '">';
                        html = html + '<input type="hidden" class="price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="quantity" value="' + parseFloat(val.products[i]['quantity']) + '">';
                        html = html + '<input type="hidden" class="extra_price" value="' + parseFloat(productExtras) + '">';
                        html = html + '<input type="hidden" class="item_price" value="' + parseFloat(val.products[i]['price']) + '">';
                        html = html + '<input type="hidden" class="extra" value="' + extras + '">';
                        html = html + '<input type="hidden" class="size" value="' + size + '">';
                        html = html + '<input type="hidden" class="taxSetting" value=\'' + JSON.stringify(val.products[i].taxSetting) + '\'>';
                        if (val.products[i]['variant_info']) {
                            html = html + '<input type="hidden" class="variant_info" value="' + btoa(JSON.stringify(val.products[i]['variant_info'])) + '">';
                        }
                        html = html + '<input type="hidden" class="category_id" value="' + val.products[i]['category_id'] + '">';
                        html = html + '</div>';
                    }
                    
                    html = html + '<input type="hidden" class="restid_' + String(order_id) + '" value="' + val.vendor.id + '">';
                    html = html + '<input type="hidden" class="resttitle_' + String(order_id) + '" value="' + val.vendor.title + '">';
                    html = html + '<input type="hidden" class="restlocation_' + String(order_id) + '" value="' + val.vendor.location + '">';
                    html = html + '<input type="hidden" class="restlatitude_' + String(order_id) + '" value="' + val.vendor.latitude + '">';
                    html = html + '<input type="hidden" class="restlongitude_' + String(order_id) + '" value="' +val.vendor.longitude + '">';
                    html = html + '<input type="hidden" class="restphoto_' + String(order_id) + '" value="' + val.vendor.photo + '">';
                    html = html + '<input type="hidden" class="deliveryCharge_' + String(order_id) + '" value="' + deliveryCharge + '">';
                    html = html + '<input type="hidden" class="specialDiscount_' + String(order_id) + '" value="' + special_discount + '">';
                    html = html + '</div><div class="text-muted m-0 ml-auto mr-3 small">Total Payment<br><span class="text-dark font-weight-bold">' + order_total_val + '</span></div> <div class="text-right">';
                    if (showReorder && !inValidVendors.includes(val.vendor.author)) {
                        html = html + '<a href="javascript:void(0);" class="btn btn-primary px-3 reorder-add-to-cart" data-id="' + String(order_id) + '">Reorder</a>';
                    }
                    html = html + '<a href="' + view_contact + '" class="btn btn-outline-primary px-3">Help</a> </div></div></div></div></div></div>';
                }
            }
        }
        if (html == '') {
            html = html + "<p class='text-center font-weight-bold h5 mt-3'>{{ trans('lang.no_results') }}</p>";
        }
        jQuery("#overlay").hide();
        return html;
    }
    
</script>
