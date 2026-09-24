@include('layouts.app')
@include('layouts.header')
<div class="siddhi-popular">
    <div class="container">

        <div class="row page-titles mb-3">
            <div class="col-md-12">
                <h3 class="font-weight-bold">{{ trans('lang.subscription_plans_title') }}</h3>
                <p class="text-muted mb-0">{{ trans('lang.subscription_plans_intro') }}</p>
            </div>
        </div>

        {{-- What the customer has right now. Hidden until it is known. --}}
        <div id="current_subscription" class="mb-3" style="display:none;">
            <div class="p-3 rounded shadow-sm bg-white">
                <h6 class="font-weight-bold mb-1">{{ trans('lang.subscription_current') }}</h6>
                <p class="mb-0" id="current_subscription_name"></p>
                <p class="mb-0 small" id="current_subscription_expiry"></p>
            </div>
        </div>

        <div class="mb-4 p-3 rounded shadow-sm bg-white d-flex align-items-center">
            <div>
                <span class="text-muted small d-block">{{ trans('lang.subscription_wallet_balance') }}</span>
                <h6 class="font-weight-bold mb-0" id="wallet_balance">-</h6>
            </div>
            <a href="{{ route('transactions') }}" class="btn btn-outline-secondary btn-sm ml-auto">
                {{ trans('lang.subscription_top_up') }}
            </a>
        </div>

        <div id="subscription_status" class="alert" style="display:none;"></div>

        <div class="text-center py-5 not_found_div" style="display:none;">
            <p class="font-weight-bold text-dark h5">{{ trans('lang.subscription_none_available') }}</p>
        </div>

        <div id="plans_list" class="row"></div>

    </div>
</div>
@include('layouts.footer')
<script type="text/javascript">
    /* Customer subscription plans, and buying one.
     *
     * Specified in the admin panel's docs/app-spec-customer-subscription.md.
     *
     * PAID FROM THE WALLET. The wallet is already funded by the existing
     * top-up flow, which carries all twelve gateways, so buying a plan needs no
     * new gateway integration - only a debit. Card gateways can be added later
     * beside this without changing what is written.
     *
     * THE WEB PANEL OWNS THE WRITE. This answers the open question in the spec:
     * after payment, this panel writes the subscription onto the customer's own
     * user document and adds a subscription_history row, exactly as the admin
     * panel does for a vendor. No backend endpoint is involved.
     */

    var plansList = document.getElementById('plans_list');
    var plansById = {};
    var walletBalance = 0;
    var regionCurrency = null;

    $(document).ready(async function () {
        jQuery("#overlay").show();

        await discoveryRegionsReady;
        regionCurrency = await getRegionCurrency();

        await loadWalletBalance();
        await renderCurrentSubscription();
        await renderPlans();

        jQuery("#overlay").hide();
    });

    async function loadWalletBalance() {
        if (cuser_id == '') {
            return;
        }
        var snapshot = await database.collection('users').doc(cuser_id).get();
        var user = snapshot.exists ? snapshot.data() : null;
        walletBalance = parseFloat((user && user.wallet_amount) || 0) || 0;
        $('#wallet_balance').text(formatCurrency(walletBalance, regionCurrency));
    }

    /* The customer's own subscription state, read from their user document -
     * exactly the fields a vendor subscription uses, so the app and the panels
     * read one shape. */
    async function renderCurrentSubscription() {
        if (cuser_id == '') {
            return;
        }

        var snapshot = await database.collection('users').doc(cuser_id).get();
        var user = snapshot.exists ? snapshot.data() : null;
        var plan = user ? user.subscription_plan : null;

        /* A vendor plan on a customer record must never read as a customer
         * subscription. Absent planFor means vendor, so it is excluded too. */
        if (!plan || plan.planFor !== 'customer') {
            $('#current_subscription').hide();
            return;
        }

        $('#current_subscription_name').text(plan.name || '');

        var expiry = user.subscriptionExpiryDate;
        if (!expiry) {
            $('#current_subscription_expiry').text("{{ trans('lang.subscription_never_expires') }}");
        } else {
            var expiryDate = expiry.toDate ? expiry.toDate() : new Date(expiry);
            var label = expiryDate < new Date()
                ? "{{ trans('lang.subscription_expired') }}"
                : "{{ trans('lang.subscription_expires_on') }} " + expiryDate.toDateString();
            $('#current_subscription_expiry').text(label);
        }

        $('#current_subscription').show();
    }

    async function renderPlans() {
        /* planFor == 'customer' is the whole separation. A plan without the
         * field is a vendor plan and must never appear here. */
        var snapshots = await database.collection('subscription_plans')
            .where('planFor', '==', 'customer')
            .where('isEnable', '==', true)
            .get();

        var activeRegionId = await getActiveRegionId();
        var html = '';
        var shown = 0;
        plansById = {};

        snapshots.docs.forEach(function (doc) {
            var plan = doc.data();
            plan.id = plan.id || doc.id;

            if (!planSoldInRegion(plan, activeRegionId)) {
                return;
            }

            plansById[plan.id] = plan;
            shown++;
            html += buildPlanCard(plan);
        });

        if (shown === 0) {
            $('.not_found_div').show();
            plansList.innerHTML = '';
            return;
        }

        $('.not_found_div').hide();
        plansList.innerHTML = html;
    }

    /* `regionIds` empty or absent means the plan is sold everywhere - the same
     * rule payment gateways and sections use. An unresolved region shows every
     * plan rather than none. */
    function planSoldInRegion(plan, regionId) {
        var ids = plan.regionIds;
        if (!Array.isArray(ids) || ids.length === 0) {
            return true;
        }
        if (!regionId) {
            return true;
        }
        return ids.indexOf(regionId) !== -1;
    }

    function buildPlanCard(plan) {
        var price = parseFloat(plan.price || 0) || 0;
        var affordable = walletBalance >= price;

        /* expiryDay is days, with "-1" meaning it never expires. The spec is
         * explicit that this already expresses monthly vs annual, so there is
         * no separate billing-period field to read. */
        var duration = (String(plan.expiryDay) === '-1')
            ? "{{ trans('lang.subscription_never_expires') }}"
            : plan.expiryDay + ' ' + "{{ trans('lang.subscription_days') }}";

        var image = plan.image
            ? '<img alt="" src="' + plan.image + '" class="img-fluid rounded mb-3" style="max-height:140px;">'
            : '';

        var button = affordable
            ? '<button type="button" class="btn btn-primary btn-block subscribe-btn" data-id="' + plan.id + '">' +
              "{{ trans('lang.subscription_pay_with_wallet') }}" + '</button>'
            : '<button type="button" class="btn btn-secondary btn-block" disabled>' +
              "{{ trans('lang.subscription_insufficient') }}" + '</button>';

        return '<div class="col-md-4 mb-4">' +
            '<div class="p-3 rounded shadow-sm bg-white h-100 d-flex flex-column">' +
            image +
            '<h6 class="font-weight-bold mb-1">' + (plan.name || '') + '</h6>' +
            '<p class="text-muted small flex-grow-1">' + (plan.description || '') + '</p>' +
            '<h5 class="font-weight-bold mb-1">' + formatCurrency(price, regionCurrency) + '</h5>' +
            '<p class="text-muted small mb-3">' + duration + '</p>' +
            button +
            '</div></div>';
    }

    $(document).on('click', '.subscribe-btn', async function () {
        var plan = plansById[$(this).attr('data-id')];
        if (!plan) {
            return;
        }

        var price = parseFloat(plan.price || 0) || 0;
        var message = "{{ trans('lang.subscription_confirm') }}"
            .replace(':plan', plan.name || '')
            .replace(':price', formatCurrency(price, regionCurrency));

        if (!window.confirm(message)) {
            return;
        }

        $('.subscribe-btn').prop('disabled', true);
        jQuery("#overlay").show();

        try {
            await purchasePlan(plan, price);
            showStatus("{{ trans('lang.subscription_purchased') }}", false);
            await loadWalletBalance();
            await renderCurrentSubscription();
            await renderPlans();
        } catch (err) {
            showStatus(
                err && err.message === 'INSUFFICIENT'
                    ? "{{ trans('lang.subscription_failed_balance') }}"
                    : "{{ trans('lang.subscription_failed') }}",
                true
            );
            $('.subscribe-btn').prop('disabled', false);
        }

        jQuery("#overlay").hide();
    });

    /* One Firestore transaction for the whole purchase.
     *
     * The stock pattern elsewhere in this panel reads the balance, then writes
     * it back separately. That can be made to pay twice by two quick clicks or
     * two tabs. Money is worth the stronger primitive: the balance is re-read
     * INSIDE the transaction and the whole thing is rejected if it moved, so
     * the debit, the wallet row, the subscription and its history row either
     * all happen or none do. */
    async function purchasePlan(plan, price) {
        var walletId = database.collection('tmp').doc().id;
        var historyId = database.collection('tmp').doc().id;

        await database.runTransaction(async function (tx) {
            var userRef = database.collection('users').doc(cuser_id);
            var snapshot = await tx.get(userRef);
            var user = snapshot.exists ? snapshot.data() : {};

            var balance = parseFloat(user.wallet_amount || 0) || 0;
            if (balance < price) {
                throw new Error('INSUFFICIENT');
            }

            /* expiryDay "-1" means it never expires, and the field is then
             * null - the same shape a vendor subscription uses. */
            var expiry = null;
            if (String(plan.expiryDay) !== '-1') {
                var expiryDate = new Date();
                expiryDate.setDate(expiryDate.getDate() + parseInt(plan.expiryDay, 10));
                expiry = firebase.firestore.Timestamp.fromDate(expiryDate);
            }

            tx.update(userRef, {
                'wallet_amount': balance - price,
                'subscriptionPlanId': plan.id,
                /* A snapshot, not a reference: a later price change must not
                 * alter what this customer bought. */
                'subscription_plan': plan,
                'subscriptionExpiryDate': expiry
            });

            tx.set(database.collection('wallet').doc(walletId), {
                'id': walletId,
                'amount': price,
                'date': firebase.firestore.FieldValue.serverTimestamp(),
                'isTopUp': false,
                'note': 'Subscription purchase',
                'payment_method': 'Wallet',
                'payment_status': 'success',
                'transactionUser': 'user',
                'user_id': cuser_id
            });

            tx.set(database.collection('subscription_history').doc(historyId), {
                'id': historyId,
                'user_id': cuser_id,
                'subscription_plan': plan,
                'expiry_date': expiry,
                'payment_type': 'Wallet',
                'createdAt': firebase.firestore.FieldValue.serverTimestamp()
            });
        });
    }

    function showStatus(message, isError) {
        $('#subscription_status')
            .text(message)
            .removeClass('alert-success alert-danger')
            .addClass(isError ? 'alert-danger' : 'alert-success')
            .show();
    }
</script>
