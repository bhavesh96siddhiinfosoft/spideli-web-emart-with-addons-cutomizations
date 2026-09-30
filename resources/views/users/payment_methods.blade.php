@include('layouts.app')
@include('layouts.header')
{{-- The mobile bar every other account screen carries. --}}
<div class="d-none">
    <div class="bg-primary p-3 d-flex align-items-center">
        <a class="toggle togglew toggle-2" href="#"><span></span></a>
        <h4 class="font-weight-bold m-0 text-white">{{ trans('lang.payment_methods_title') }}</h4>
    </div>
</div>
<div class="siddhi-popular">
    <div class="container">
        <div class="py-5">

            <h2 class="font-weight-bold mb-1">{{ trans('lang.payment_methods_title') }}</h2>
            <p class="text-muted mb-4">{{ trans('lang.payment_methods_intro') }}</p>

            <div id="payment_methods_error" class="alert alert-danger" style="display:none;"></div>

            <div class="row">
                <div class="col-md-7 mb-3">
                    <div id="payment_methods_empty" class="p-4 rounded shadow-sm bg-white text-center" style="display:none;">
                        <i class="fa fa-credit-card h4 text-muted mb-2 d-block"></i>
                        <p class="text-muted mb-0">{{ trans('lang.payment_methods_none') }}</p>
                    </div>

                    <div id="payment_methods_list"></div>
                </div>

                <div class="col-md-5 mb-3">
                    <div class="p-4 rounded shadow-sm bg-white sticky_sidebar">
                        <h5 class="font-weight-bold mb-3" id="payment_method_form_title">
                            {{ trans('lang.payment_methods_add') }}
                        </h5>

                        <div class="form-group">
                            <label>{{ trans('lang.payment_methods_operator') }}</label>
                            {{-- Free text with suggestions rather than a fixed
                                 list. Only "Orange Money" is confirmed from live
                                 data; the rest are common in these markets but
                                 are not a list the client has given us, and a
                                 customer whose operator is missing must not be
                                 stuck. --}}
                            <input type="text" class="form-control" id="pm_operator" list="pm_operator_options"
                                   maxlength="60" autocomplete="off">
                            <datalist id="pm_operator_options">
                                <option value="Orange Money"></option>
                                <option value="MTN Mobile Money"></option>
                                <option value="Moov Money"></option>
                                <option value="Wave"></option>
                                <option value="Free Money"></option>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label>{{ trans('lang.payment_methods_number') }}</label>
                            <input type="tel" class="form-control" id="pm_number" maxlength="24" autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label>{{ trans('lang.payment_methods_label') }}</label>
                            <input type="text" class="form-control" id="pm_label" maxlength="40" autocomplete="off"
                                   placeholder="{{ trans('lang.payment_methods_label_hint') }}">
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="pm_default">
                                <label class="custom-control-label" for="pm_default">
                                    {{ trans('lang.payment_methods_make_default') }}
                                </label>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" id="pm_save">
                            {{ trans('lang.save') }}
                        </button>

                        <button type="button" class="btn btn-link btn-block" id="pm_cancel" style="display:none;">
                            {{ trans('lang.close') }}
                        </button>

                        <p class="text-muted small mb-0 mt-3">
                            <i class="fa fa-lock mr-1"></i>{{ trans('lang.payment_methods_no_cards') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@include('layouts.footer')
@include('layouts.nav')
<script type="text/javascript">
    /* Saved mobile money numbers.
     *
     * The shape belongs to the customer app - APP-SPEC-CUSTOMER-APP.md section
     * 7 - and was confirmed against a live record on 30 September:
     *
     *     { id, type, operator, number, isDefault, regionId }
     *
     * with label optional. Nothing here is invented.
     *
     * THIS SCREEN STORES; IT DOES NOT SPEND. No payment method in this panel
     * collects a number - they are hosted redirects - so there is nothing at
     * checkout to prefill. The app is what reads these.
     */

    var pmUserId = user_uuid;
    var pmRef = firebase.firestore().collection('users').doc(pmUserId);

    /* The list as last read. Every write re-reads first, so this is only ever
     * used for drawing. */
    var pmMethods = [];

    /* The id being edited, or '' when the form is adding. */
    var pmEditingId = '';

    $(document).ready(async function () {
        jQuery("#overlay").show();

        try {
            await loadPaymentMethods();
        } catch (e) {
            console.error('saved payment methods could not be read', e);
            pmError("{{ trans('lang.payment_methods_read_failed') }}");
        }

        jQuery("#overlay").hide();
    });

    async function loadPaymentMethods() {
        var snapshot = await pmRef.get();
        pmMethods = readMethods(snapshot);
        renderPaymentMethods();
    }

    /* Anything that is not an array reads as no methods. A customer who has
     * never saved one has no field at all. */
    function readMethods(snapshot) {
        if (!snapshot.exists) {
            return [];
        }

        var saved = snapshot.data().savedPaymentMethods;

        return Array.isArray(saved) ? saved : [];
    }

    function renderPaymentMethods() {
        var html = '';

        pmMethods.forEach(function (method) {
            html += paymentMethodCard(method);
        });

        $('#payment_methods_list').html(html);
        $('#payment_methods_empty').toggle(pmMethods.length === 0);
    }

    function paymentMethodCard(method) {
        var badge = method.isDefault
            ? '<span class="badge badge-success ml-2">' + "{{ trans('lang.payment_methods_default') }}" + '</span>'
            : '';

        var makeDefault = method.isDefault
            ? ''
            : '<button type="button" class="btn btn-sm btn-outline-secondary mr-2 pm-default-btn" data-id="' +
              pmAttr(method.id) + '">' + "{{ trans('lang.payment_methods_make_default') }}" + '</button>';

        return '<div class="p-3 mb-3 rounded shadow-sm bg-white">' +
            '<div class="d-flex align-items-center mb-1">' +
                '<h6 class="font-weight-bold m-0">' + pmEscape(method.operator || '-') + '</h6>' + badge +
            '</div>' +
            '<p class="mb-1">' + pmEscape(method.number || '') + '</p>' +
            (method.label ? '<p class="text-muted small mb-2">' + pmEscape(method.label) + '</p>' : '') +
            '<div class="mt-2">' +
                makeDefault +
                '<button type="button" class="btn btn-sm btn-outline-secondary mr-2 pm-edit-btn" data-id="' +
                    pmAttr(method.id) + '">' + "{{ trans('lang.edit') }}" + '</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger pm-delete-btn" data-id="' +
                    pmAttr(method.id) + '">' + "{{ trans('lang.delete') }}" + '</button>' +
            '</div>' +
        '</div>';
    }

    function pmEscape(value) {
        return $('<div></div>').text(value === undefined || value === null ? '' : value).html();
    }

    function pmAttr(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function pmError(message) {
        $('#payment_methods_error').text(message).show();
        window.scrollTo(0, 0);
    }

    function pmFind(id) {
        for (var i = 0; i < pmMethods.length; i++) {
            if (String(pmMethods[i].id) === String(id)) {
                return pmMethods[i];
            }
        }

        return null;
    }

    function pmUuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        var bytes = new Uint8Array(16);

        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            window.crypto.getRandomValues(bytes);
        } else {
            for (var i = 0; i < bytes.length; i++) {
                bytes[i] = Math.floor(Math.random() * 256);
            }
        }

        var out = '';

        for (var j = 0; j < bytes.length; j++) {
            out += ('0' + bytes[j].toString(16)).slice(-2);
        }

        return out;
    }

    /* The app carries two types and the customer should not have to understand
     * the difference. Wave is an operator to them, so the type follows from
     * what they typed rather than asking. */
    function pmTypeFor(operator) {
        return String(operator || '').trim().toLowerCase() === 'wave' ? 'wave' : 'mobile_money';
    }

    /* Digits only for the length test - a customer may type spaces, dashes or a
     * country code and none of those change how long the number is. */
    function pmDigits(number) {
        return String(number || '').replace(/[^0-9]/g, '');
    }

    $('#payment_methods_list').on('click', '.pm-edit-btn', function () {
        var method = pmFind($(this).attr('data-id'));

        if (!method) {
            return;
        }

        pmEditingId = method.id;
        $('#pm_operator').val(method.operator || '');
        $('#pm_number').val(method.number || '');
        $('#pm_label').val(method.label || '');
        $('#pm_default').prop('checked', method.isDefault === true);
        $('#payment_method_form_title').text("{{ trans('lang.payment_methods_edit') }}");
        $('#pm_cancel').show();
        $('#payment_methods_error').hide();
    });

    $('#pm_cancel').on('click', function () {
        pmResetForm();
    });

    function pmResetForm() {
        pmEditingId = '';
        $('#pm_operator').val('');
        $('#pm_number').val('');
        $('#pm_label').val('');
        $('#pm_default').prop('checked', false);
        $('#payment_method_form_title').text("{{ trans('lang.payment_methods_add') }}");
        $('#pm_cancel').hide();
        $('#payment_methods_error').hide();
    }

    $('#payment_methods_list').on('click', '.pm-default-btn', async function () {
        var id = $(this).attr('data-id');

        jQuery("#overlay").show();

        try {
            await pmWrite(function (methods) {
                return pmApplyDefault(methods, id);
            });
        } catch (e) {
            console.error('default payment method could not be set', e);
            pmError("{{ trans('lang.payment_methods_save_failed') }}");
        }

        jQuery("#overlay").hide();
    });

    $('#payment_methods_list').on('click', '.pm-delete-btn', async function () {
        var id = $(this).attr('data-id');
        var method = pmFind(id);

        var confirmed = await Swal.fire({
            text: "{{ trans('lang.payment_methods_delete_confirm') }}",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "{{ trans('lang.delete') }}",
            cancelButtonText: "{{ trans('lang.close') }}"
        });

        if (!confirmed.isConfirmed) {
            return;
        }

        jQuery("#overlay").show();

        try {
            await pmWrite(function (methods) {
                return pmApplyDelete(methods, id);
            });

            if (String(pmEditingId) === String(id)) {
                pmResetForm();
            }
        } catch (e) {
            console.error('payment method could not be deleted', e);
            pmError("{{ trans('lang.payment_methods_save_failed') }}");
        }

        jQuery("#overlay").hide();
    });

    $('#pm_save').on('click', async function () {
        var operator = $.trim($('#pm_operator').val());
        var number = $.trim($('#pm_number').val());
        var label = $.trim($('#pm_label').val());
        var makeDefault = $('#pm_default').is(':checked');

        $('#payment_methods_error').hide();

        if (operator === '') {
            pmError("{{ trans('lang.payment_methods_operator_required') }}");
            return;
        }

        if (pmDigits(number).length < 6) {
            pmError("{{ trans('lang.payment_methods_number_required') }}");
            return;
        }

        jQuery("#overlay").show();

        var editingId = pmEditingId;

        try {
            /* regionId is written from the ACTIVE REGION, matching the live
             * record the app wrote. It belongs to the METHOD, not to the
             * customer - a mobile money number is usable where its operator
             * operates - so this is not the forbidden "region on a customer".
             * Omitted entirely when no region resolves, rather than guessed. */
            var regionId = '';

            try {
                regionId = await getActiveRegionId();
            } catch (e) {
                console.warn('region could not be resolved for this payment method', e);
            }

            await pmWrite(function (methods) {
                return pmApplyUpsert(methods, editingId, {
                    operator: operator,
                    number: number,
                    label: label,
                    isDefault: makeDefault
                }, regionId);
            });

            pmResetForm();
        } catch (e) {
            console.error('payment method could not be saved', e);
            pmError("{{ trans('lang.payment_methods_save_failed') }}");
        }

        jQuery("#overlay").hide();
    });

    /* ---- The three write rules ------------------------------------------
     * Named and separated from the buttons so each can be stated once, read
     * on its own, and tested without a browser. Each takes the list as
     * STORED and returns the list to store.
     * -------------------------------------------------------------------- */

    function pmApplyDefault(methods, id) {
        return methods.map(function (method) {
            method.isDefault = String(method.id) === String(id);
            return method;
        });
    }

    function pmApplyDelete(methods, id) {
        var removed = null;

        var kept = methods.filter(function (method) {
            if (String(method.id) === String(id)) {
                removed = method;
                return false;
            }

            return true;
        });

        /* DELETING THE DEFAULT LEAVES NOBODY DEFAULT, so the first remaining
         * one takes it. Saved methods with none marked hands the app a silent
         * choice between them. */
        if (removed && removed.isDefault === true && kept.length > 0) {
            kept[0].isDefault = true;
        }

        return kept;
    }

    function pmApplyUpsert(methods, editingId, values, regionId) {
        var existing = null;

        methods.forEach(function (method) {
            if (String(method.id) === String(editingId)) {
                existing = method;
            }
        });

        var entry = existing || { id: pmUuid() };

        entry.type = pmTypeFor(values.operator);
        entry.operator = values.operator;
        entry.number = values.number;
        entry.isDefault = values.isDefault === true;

        if (values.label) {
            entry.label = values.label;
        } else {
            delete entry.label;
        }

        /* NEVER CLEAR A regionId THE APP WROTE just because this browser could
         * not resolve one. */
        if (regionId) {
            entry.regionId = regionId;
        }

        if (!existing) {
            methods.push(entry);
        }

        /* THE FIRST ONE SAVED IS THE DEFAULT whatever the box said - a single
         * saved method that is not the default is a state with no meaning. */
        if (methods.length === 1) {
            methods[0].isDefault = true;
        } else if (entry.isDefault) {
            methods.forEach(function (method) {
                if (method !== entry) {
                    method.isDefault = false;
                }
            });
        }

        return methods;
    }

    /* Every write RE-READS FIRST and applies the change to what is actually
     * stored, because the customer app writes this same array. Taking the copy
     * this page loaded and writing it back would silently drop a number added
     * on the phone while this tab sat open. */
    async function pmWrite(change) {
        var snapshot = await pmRef.get();
        var methods = change(readMethods(snapshot));

        await pmRef.update({ 'savedPaymentMethods': methods });

        pmMethods = methods;
        renderPaymentMethods();
    }
</script>
