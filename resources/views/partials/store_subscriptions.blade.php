{{--
    Subscriptions a STORE sells to its own customers.

    Document 1 point 19 - the restaurant selling a monthly bread delivery.
    Specified in the store repo's docs/APP-SPEC-STORE.md section 4, which the
    store and admin panels already implement. This is the customer half: until
    now a store could create a plan and nobody could buy it.

    THIS IS THE THIRD SUBSCRIPTION SYSTEM AND MUST NOT TOUCH THE OTHER TWO.
    The platform's own plans live in `subscription_plans` and write
    subscriptionPlanId / subscription_plan / subscriptionExpiryDate onto the
    customer. A store's plans live in their own collections and write nothing
    onto the customer document at all - a customer can hold a platform history
    plan and a bakery's bread plan at the same time, and one pair of fields
    cannot hold both.

    Included by the store page. Renders nothing at all for a store with no
    plans, which is every store today.
--}}
<div id="store_subscriptions" class="mb-4" style="display:none;">
    <div class="bg-white rounded shadow-sm p-3">
        <h5 class="font-weight-bold mb-1">{{ trans('lang.store_subscriptions_title') }}</h5>
        <p class="text-muted small mb-3">{{ trans('lang.store_subscriptions_intro') }}</p>

        <div id="store_subscription_status" class="alert" style="display:none;"></div>

        <div id="store_subscription_current" class="mb-3" style="display:none;">
            <div class="p-3 rounded border">
                <h6 class="font-weight-bold mb-1">{{ trans('lang.store_subscription_current') }}</h6>
                <p class="mb-0" id="store_subscription_current_name"></p>
                <p class="mb-0 small" id="store_subscription_current_expiry"></p>
            </div>
        </div>

        <div id="store_subscription_plans" class="row"></div>
    </div>
</div>

<script type="text/javascript">
    /* The store whose page this is, its plans, and what the customer already
     * holds from it. Read once on load. */
    var storeSubscriptionPlans = {};
    var storeSubscriptionCurrency = null;
    var storeSubscriptionHeld = null;
    var storeSubscriptionVendor = null;
    var storeSubscriptionWallet = 0;

    async function loadStoreSubscriptions(vendor) {
        if (!vendor || !vendor.id) {
            return;
        }

        storeSubscriptionVendor = vendor;
        storeSubscriptionCurrency = await getCurrencyForStore(vendor);

        var snapshots = await database.collection('vendor_subscription_plans')
            .where('vendorID', '==', vendor.id)
            .where('isEnable', '==', true)
            .get();

        if (snapshots.empty) {
            return;
        }

        var activeRegionId = await getActiveRegionId();
        var html = '';
        var shown = 0;
        storeSubscriptionPlans = {};

        await loadHeldStoreSubscription(vendor.id);
        await loadStoreSubscriptionWallet();

        snapshots.docs.forEach(function (doc) {
            var plan = doc.data();
            plan.id = plan.id || doc.id;

            if (!storePlanSoldHere(plan, activeRegionId)) {
                return;
            }

            storeSubscriptionPlans[plan.id] = plan;
            shown++;
            html += buildStorePlanCard(plan);
        });

        if (shown === 0) {
            return;
        }

        document.getElementById('store_subscription_plans').innerHTML = html;
        renderHeldStoreSubscription();
        $('#store_subscriptions').show();
    }

    /* A plan belongs to one store, and a store to one region, so the plan
     * carries a single regionId rather than a list. An empty one means the
     * store has no region set - show it rather than hide the store's own
     * offer, which matches how an unresolved region is treated elsewhere. */
    function storePlanSoldHere(plan, regionId) {
        if (!plan.regionId || !regionId) {
            return true;
        }

        return String(plan.regionId) === String(regionId);
    }

    async function loadStoreSubscriptionWallet() {
        storeSubscriptionWallet = 0;

        if (cuser_id == '') {
            return;
        }

        var snapshot = await database.collection('users').doc(cuser_id).get();
        var user = snapshot.exists ? snapshot.data() : null;
        storeSubscriptionWallet = parseFloat((user && user.wallet_amount) || 0) || 0;
    }

    /* What this customer already holds FROM THIS STORE. A cancelled or expired
     * one does not count as held - it is offered again instead. */
    async function loadHeldStoreSubscription(vendorID) {
        storeSubscriptionHeld = null;

        if (cuser_id == '') {
            return;
        }

        var snapshots = await database.collection('vendor_subscriptions')
            .where('vendorID', '==', vendorID)
            .where('customerId', '==', cuser_id)
            .get();

        snapshots.docs.forEach(function (doc) {
            var subscription = doc.data();

            if ((subscription.status || 'active') === 'cancelled') {
                return;
            }

            var expiry = storeSubscriptionDate(subscription.expiryDate);
            if (expiry !== null && expiry.getTime() < Date.now()) {
                return;
            }

            /* If more than one is somehow live, the one running longest wins -
             * never the one that expires soonest. */
            if (storeSubscriptionHeld === null) {
                storeSubscriptionHeld = subscription;
                return;
            }

            var heldExpiry = storeSubscriptionDate(storeSubscriptionHeld.expiryDate);
            if (heldExpiry !== null && expiry !== null && expiry > heldExpiry) {
                storeSubscriptionHeld = subscription;
            }
        });
    }

    function storeSubscriptionDate(value) {
        if (!value) {
            return null;
        }
        if (typeof value.toDate === 'function') {
            return value.toDate();
        }
        var date = new Date(value);
        return isNaN(date.getTime()) ? null : date;
    }

    function renderHeldStoreSubscription() {
        if (!storeSubscriptionHeld) {
            $('#store_subscription_current').hide();
            return;
        }

        var plan = storeSubscriptionHeld.plan || {};
        $('#store_subscription_current_name').text(plan.title || '');

        var expiry = storeSubscriptionDate(storeSubscriptionHeld.expiryDate);
        $('#store_subscription_current_expiry').text(
            expiry === null
                ? "{{ trans('lang.subscription_never_expires') }}"
                : "{{ trans('lang.subscription_expires_on') }} " + expiry.toDateString()
        );

        $('#store_subscription_current').show();
    }

    function buildStorePlanCard(plan) {
        var price = parseFloat(plan.price || 0) || 0;
        var held = storeSubscriptionHeld !== null
            && storeSubscriptionHeld.planId
            && String(storeSubscriptionHeld.planId) === String(plan.id);
        var affordable = storeSubscriptionWallet >= price;

        /* expiryDay is days, as the store panel writes it. "-1" means it never
         * expires, the same convention the platform plans use. */
        var duration = (String(plan.expiryDay) === '-1')
            ? "{{ trans('lang.subscription_never_expires') }}"
            : plan.expiryDay + ' ' + "{{ trans('lang.subscription_days') }}";

        var image = plan.photo
            ? '<img alt="" src="' + plan.photo + '" class="img-fluid rounded mb-3" style="max-height:140px;">'
            : '';

        /* What the customer actually gets, as the store wrote it. */
        var points = '';
        if (Array.isArray(plan.plan_points) && plan.plan_points.length > 0) {
            points = '<ul class="small text-muted pl-3 mb-2">';
            plan.plan_points.forEach(function (point) {
                if (point) {
                    points += '<li>' + $('<div>').text(point).html() + '</li>';
                }
            });
            points += '</ul>';
        }

        /* Two ways to pay. The wallet buys it outright when there is
         * enough in it; any other method goes through the top-up screen,
         * which carries every gateway the customer's region allows, and the
         * subscription finishes by itself on the way back. */
        var button;
        if (cuser_id == '') {
            button = '<a href="{{ route('login') }}" class="btn btn-outline-primary btn-block">' +
                "{{ trans('lang.store_subscription_sign_in') }}" + '</a>';
        } else if (held) {
            button = '<button type="button" class="btn btn-outline-success btn-block" disabled>' +
                "{{ trans('lang.subscription_current_button') }}" + '</button>';
        } else {
            button = '';

            if (affordable) {
                button += '<button type="button" class="btn btn-primary btn-block store-subscribe-btn" data-id="' + plan.id + '">' +
                    "{{ trans('lang.subscription_pay_with_wallet') }}" + '</button>';
            }

            button += '<button type="button" class="btn btn-outline-primary btn-block store-topup-btn" data-id="' + plan.id + '">' +
                (affordable
                    ? "{{ trans('lang.store_subscription_other_method') }}"
                    : "{{ trans('lang.store_subscription_topup_and_pay') }}") +
                '</button>';

            if (!affordable) {
                button += '<p class="small text-muted mt-2 mb-0">' +
                    "{{ trans('lang.subscription_insufficient') }}" + '</p>';
            }
        }

        var badge = held
            ? '<span class="badge badge-success align-self-start mb-2">' +
              "{{ trans('lang.subscription_current_badge') }}" + '</span>'
            : '';

        return '<div class="col-md-4 mb-3">' +
            '<div class="p-3 rounded border h-100 d-flex flex-column' + (held ? ' border-success' : '') + '">' +
            badge +
            image +
            '<h6 class="font-weight-bold mb-1">' + $('<div>').text(plan.title || '').html() + '</h6>' +
            '<p class="text-muted small mb-2">' + $('<div>').text(plan.description || '').html() + '</p>' +
            points +
            '<div class="mt-auto">' +
            '<h5 class="font-weight-bold mb-1">' + formatCurrency(price, storeSubscriptionCurrency) + '</h5>' +
            '<p class="text-muted small mb-3">' + duration + '</p>' +
            button +
            '</div>' +
            '</div></div>';
    }

    /* A NATIVE delegated listener, not $(document).on(...).
     *
     * This partial is included in the body, above the products, so its script
     * runs BEFORE the footer loads jQuery - $ does not exist yet at this
     * point. Everything else here only runs on click or from the page's ready
     * handler, by which time it does.
     */
    document.addEventListener('click', async function (event) {
        var button = (event.target && event.target.closest)
            ? event.target.closest('.store-subscribe-btn')
            : null;

        if (!button) {
            return;
        }

        var plan = storeSubscriptionPlans[button.getAttribute('data-id')];
        if (!plan) {
            return;
        }

        /* Checked again here because this is where the money moves. A disabled
         * button is a courtesy, not a guard. */
        if (storeSubscriptionHeld && String(storeSubscriptionHeld.planId) === String(plan.id)) {
            return;
        }

        var price = parseFloat(plan.price || 0) || 0;
        var message = "{{ trans('lang.subscription_confirm') }}"
            .replace(':plan', plan.title || '')
            .replace(':price', formatCurrency(price, storeSubscriptionCurrency));

        if (!window.confirm(message)) {
            return;
        }

        $('.store-subscribe-btn').prop('disabled', true);
        jQuery("#overlay").show();

        try {
            await purchaseStorePlanFromWallet(plan, storeSubscriptionVendor);
            showStoreSubscriptionStatus("{{ trans('lang.store_subscription_purchased') }}", false);
            await loadHeldStoreSubscription(plan.vendorID);
            await loadStoreSubscriptionWallet();
            redrawStorePlanCards();
        } catch (err) {
            showStoreSubscriptionStatus(
                err && err.message === 'INSUFFICIENT'
                    ? "{{ trans('lang.subscription_failed_balance') }}"
                    : "{{ trans('lang.subscription_failed') }}",
                true
            );
            $('.store-subscribe-btn').prop('disabled', false);
        }

        jQuery("#overlay").hide();
    });

    /* Off to the top-up screen with the plan's price and the plan
     * remembered. Every gateway the region carries is offered there, and the
     * subscription completes on the way back.
     *
     * The money is never at risk in between: if anything goes wrong after the
     * payment, it is sitting in the customer's wallet and the plan can be
     * bought with one press. */
    document.addEventListener('click', function (event) {
        var button = (event.target && event.target.closest)
            ? event.target.closest('.store-topup-btn')
            : null;

        if (!button) {
            return;
        }

        var plan = storeSubscriptionPlans[button.getAttribute('data-id')];
        if (!plan) {
            return;
        }

        saveStoreSubscriptionIntent(plan, window.location.href);

        var price = parseFloat(plan.price || 0) || 0;
        var owing = Math.max(0, price - storeSubscriptionWallet);

        /* Only the shortfall is asked for. A customer with 2,000 towards a
         * 2,500 plan tops up 500, not 2,500. */
        window.location.href = "{{ route('transactions') }}" +
            '?topup=' + encodeURIComponent(owing > 0 ? owing.toFixed(2) : price.toFixed(2)) +
            '&for=' + encodeURIComponent(plan.title || '');
    });

    function redrawStorePlanCards() {
        var html = '';
        Object.keys(storeSubscriptionPlans).forEach(function (id) {
            html += buildStorePlanCard(storeSubscriptionPlans[id]);
        });
        document.getElementById('store_subscription_plans').innerHTML = html;
        renderHeldStoreSubscription();
    }

    function showStoreSubscriptionStatus(message, isError) {
        $('#store_subscription_status')
            .text(message)
            .removeClass('alert-success alert-danger')
            .addClass(isError ? 'alert-danger' : 'alert-success')
            .show();
    }
</script>
