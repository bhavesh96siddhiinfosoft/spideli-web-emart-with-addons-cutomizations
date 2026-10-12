@include('layouts.app')
@include('layouts.header')
@include('parcel.partials.styles')
@php
    $countries = file_get_contents(public_path('countriesdata.json'));
    $countries = json_decode($countries);
    $countries = (array)$countries;
    $newcountries = array();
    $newcountriesjs = array();
    foreach ($countries as $keycountry => $valuecountry) {
        $newcountries[$valuecountry->phoneCode] = $valuecountry;
        $newcountriesjs[$valuecountry->phoneCode] = $valuecountry->code;
    }
@endphp
<div class="siddhi-home-page">
    <div class="bg-primary px-3 d-none mobile-filter pb-3">
        <div class="row align-items-center">
            <div class="input-group rounded shadow-sm overflow-hidden col-md-9 col-sm-9">
                <div class="input-group-prepend">
                    <button class="border-0 btn btn-outline-secondary text-dark bg-white btn-block"><i
                                class="feather-search"></i></button>
                </div>
                <input type="text" class="shadow-none border-0 form-control" placeholder="Search for vendors or dishes">
            </div>
            <div class="text-white col-md-3 col-sm-3">
                <div class="title d-flex align-items-center">
                    <a class="text-white font-weight-bold ml-auto" data-toggle="modal" data-target="#exampleModal"
                       href="#">{{trans('lang.filter')}}</a>
                </div>
            </div>
        </div>
    </div>
    <div class="parcel_delivery_content mt-5 mb-5">
        <section id="tabs">
            <div class="container">
                <div class="parcel_delivery">
                    <div class="error_top"></div>
                    <input type="hidden" class="form-control" value="" id="parcelType" name="parcelType">
                    <nav>
                        <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab"
                               href="#as-soon-as-possible" role="tab" aria-controls="as-soon-as-possible"
                               aria-selected="true"><img
                                        src="{{asset('img/asap_unselected.png')}}">{{trans('lang.as_soon_as_possible')}}
                            </a>
                            <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#schedule"
                               role="tab" aria-controls="schedule" aria-selected="false"><img
                                        src="{{asset('img/schedule_unselected.png')}}"> {{trans('lang.schedule')}}</a>
                        </div>
                    </nav> 
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="as-soon-as-possible" role="tabpanel"
                             aria-labelledby="nav-home-tab">
                            @include('parcel.partials.shipment', ['tab' => 'asap'])
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>2</span>{{trans('lang.sender_info')}}</h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control senderAddress" value=""
                                               id="senderAddress" 
                                               name="address" required="" placeholder=" ">
                                        <span for="exampleFormControlInput1">{{trans('lang.sender_address')}}</span>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="email" class="form-control senderName" value="" name="name"
                                               required="" placeholder=" ">
                                        <span for="exampleFormControlInput2">{{trans('lang.sender_name')}}</span>
                                    </div>

                                    <div class="inputField col-md-6 phone-box">
                                        <div class="col-xs-12">
                                            <select class="form-control country_selector" name="senderPhoneCountry" id="senderPhoneCountry">
                                                <?php foreach ($newcountries as $keycy => $valuecy) { ?>
                                                    <option code="<?php echo $valuecy->code; ?>" value="<?php echo $keycy; ?>">+<?php echo $valuecy->phoneCode; ?> <?php echo $valuecy->countryName; ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="text" class="form-control senderPhone" required="" placeholder=" ">
                                            <span for="exampleFormControlInput3">{{trans('lang.phone_number')}}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="inputField col-md-6">
                                        <label for="exampleFormControlTextarea1"></label>
                                        <br><br>
                                        <select class="form-control senderParcelWeight"
                                                style="border-top:none;border-left:none;border-right:none;">
                                            <option value="">{{trans('lang.select')}} {{trans('lang.parcel_weight')}}</option>
                                        </select>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control senderNote" 
                                               placeholder=" ">
                                        <span for="exampleFormControlTextarea1">{{trans('lang.note')}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>3</span> {{trans('lang.receiver_info')}}</h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control receiverAddress" value=""
                                               id="receiver_address" name="address" required="" placeholder=" ">
                                        <span for="exampleFormControlInput1">{{trans('lang.receiver_address')}}</span>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="email" class="form-control receiverName" value="" name="name"
                                               required="" placeholder=" ">
                                        <span for="exampleFormControlInput2">{{trans('lang.sender_name')}}</span>
                                    </div>
                                    <div class="inputField col-md-6 phone-box">
                                        <div class="col-xs-12">
                                            <select class="form-control country_selector" name="receiverPhoneCountry" id="receiverPhoneCountry">
                                                <?php foreach ($newcountries as $keycy => $valuecy) { ?>
                                                    <option code="<?php echo $valuecy->code; ?>" value="<?php echo $keycy; ?>">+<?php echo $valuecy->phoneCode; ?> <?php echo $valuecy->countryName; ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="text" class="form-control receiverPhone" required="" placeholder="">
                                            <span for="exampleFormControlInput3">{{trans('lang.phone_number')}}</span>
                                        </div>
                                    </div>
                                     <div class="inputField col-md-6">
                                        <input type="text" class="form-control receiverNote" 
                                               placeholder=" ">
                                        <span for="exampleFormControlTextarea1">{{trans('lang.note')}}</span>
                                    </div>
                                </div>
                            </div>
                            @include('parcel.partials.parcel_details', ['tab' => 'asap', 'number' => 4])
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>6</span> {{trans('lang.upload_parcel_image')}}</h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <div class="parcelImageUploadDiv">
                                            <input type="file" onChange="handleFileSelect(event)" class="col-7">
                                            <div class="uploding_image_photos"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row width-50">
                                    <div class="appendParcelImages">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-btn">
                                <button type="button"
                                        class="btn btn-primary parcel_btn">{{trans('lang.continue')}}</button>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="nav-profile-tab">
                            @include('parcel.partials.shipment', ['tab' => 'schedule'])
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>2</span>{{trans('lang.sender_info')}}</h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <input type="text"
                                               class="form-control senderAddress sender_address pac-target-input"
                                               value=""
                                               id="sender_address_schedule"
                                               name="address" required="" placeholder=" ">
                                        <span for="exampleFormControlInput1">{{trans('lang.sender_address')}}</span>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="email" class="form-control senderName" value="" name="name"
                                               required="" placeholder=" ">
                                        <span for="exampleFormControlInput2">{{trans('lang.sender_name')}}</span>
                                    </div>
                                    <div class="inputField col-md-6 phone-box">
                                        <div class="col-xs-12">
                                            <select class="form-control country_selector" name="senderPhoneCountry" id="senderPhoneCountry">
                                                <?php foreach ($newcountries as $keycy => $valuecy) { ?>
                                                    <option code="<?php echo $valuecy->code; ?>" value="<?php echo $keycy; ?>">+<?php echo $valuecy->phoneCode; ?> <?php echo $valuecy->countryName; ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="text" class="form-control senderPhone" required="" placeholder=" ">
                                            <span for="exampleFormControlInput3">{{trans('lang.phone_number')}}</span>
                                        </div>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <label for="exampleFormControlTextarea1"></label>
                                        <br><br>
                                        <select class="form-control senderParcelWeight"
                                                style="border-top:none;border-left:none;border-right:none;">
                                            <option value="">{{trans('lang.select')}} {{trans('lang.parcel_weight')}}</option>
                                        </select>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="datetime-local" class="form-control senderArrive" required=""
                                               placeholder="Select date & time">
                                        <span for="exampleFormControlTextarea1">{{trans('lang.when_pickup_address')}}</span>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control senderNote" 
                                               placeholder=" ">
                                        <span for="exampleFormControlTextarea1">{{trans('lang.note')}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>3</span> {{trans('lang.receiver_info')}} </h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control receiverAddress" value=""
                                               id="receiver_address_schedule" name="address" required=""
                                               placeholder="">
                                        <span for="exampleFormControlInput1">{{trans('lang.receiver_address')}}</span>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="email" class="form-control receiverName" value="" name="name"
                                               required="" placeholder=" ">
                                        <span for="exampleFormControlInput2">{{trans('lang.sender_name')}}</span>
                                    </div>
                                    <div class="inputField col-md-6 phone-box">
                                        <div class="col-xs-12">
                                            <select class="form-control country_selector" name="receiverPhoneCountry" id="receiverPhoneCountry">
                                                <?php foreach ($newcountries as $keycy => $valuecy) { ?>
                                                    <option code="<?php echo $valuecy->code; ?>" value="<?php echo $keycy; ?>">+<?php echo $valuecy->phoneCode; ?> <?php echo $valuecy->countryName; ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="text" class="form-control receiverPhone" required="" placeholder=" ">
                                            <span for="exampleFormControlInput3">{{trans('lang.phone_number')}}</span>
                                        </div>
                                    </div>
                                    <div class="inputField col-md-6">
                                        <input type="text" class="form-control receiverNote" 
                                               placeholder=" ">
                                        <span for="exampleFormControlTextarea1">{{trans('lang.note')}}</span>
                                    </div>
                                </div>
                            </div>
                            @include('parcel.partials.parcel_details', ['tab' => 'schedule', 'number' => 4])
                            <div class="tab-inner">
                                <div class="tab-title sende-title">
                                    <h3><span>6</span> {{trans('lang.upload_parcel_image')}}</h3>
                                </div>
                                <div class="row">
                                    <div class="inputField col-md-6">
                                        <div class="parcelImageUploadDiv">
                                            <input type="file" onChange="handleFileSelect(event)" class="col-7">
                                            <div class="uploding_image_photos"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row width-50">
                                    <div class="appendParcelImages">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-btn">
                                <button type="button" class="btn btn-primary parcel_btn">Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@include('layouts.footer')

<script src="{{ asset('js/geofirestore.js') }}"></script>
<script src="https://cdn.firebase.com/libs/geofire/5.0.1/geofire.min.js"></script>
<script type="text/javascript" src="{{asset('vendor/slick/slick.min.js')}}"></script>

<script type="text/javascript">

    var section_id = "<?php echo @$_COOKIE['section_id'] ?>";
    var parcelType = '';
    
    var parcelcatref = database.collection('parcel_categories').where('id', '==', '<?php echo $id; ?>');
    parcelcatref.get().then(async function (snapshots) {
        var categoryData = snapshots.docs[0].data();
        parcelType = categoryData.title;
        $('#parcelType').val(parcelType);
    });
    
    var parcelWeightRef = database.collection('parcel_weight');
    var parcelImages = [];
    var parcelImagesCount = 0;

    /* ---- Point 41 ---------------------------------------------------------
     * The app's parcel fields, on the website. Prices are NOT worked out here:
     * the server prices every option from what is live in Firestore (see
     * App\Services\ParcelPricing) once Continue is pressed. This page only
     * collects what the customer says about the parcel. */
    var senderPlace = {city: '', countryCode: '', country: ''};
    var receiverPlace = {city: '', countryCode: '', country: ''};
    var parcelPickupPoints = [];

    /* City and country from a geocoding result, saved on the order as
     * origin / destination exactly as the app does. Every part may be
     * missing - Google tags parts with several types, so membership is
     * tested, never types[0]. */
    function p41PlaceFromGoogle(result) {
        var place = {city: '', countryCode: '', country: ''};
        var components = (result && result.address_components) || [];
        var cityTypes = ['locality', 'postal_town', 'administrative_area_level_2', 'administrative_area_level_1'];
        for (var t = 0; t < cityTypes.length && place.city === ''; t++) {
            for (var i = 0; i < components.length; i++) {
                if ((components[i].types || []).indexOf(cityTypes[t]) !== -1) {
                    place.city = components[i].long_name || '';
                    break;
                }
            }
        }
        for (var j = 0; j < components.length; j++) {
            if ((components[j].types || []).indexOf('country') !== -1) {
                place.country = components[j].long_name || '';
                place.countryCode = (components[j].short_name || '').toUpperCase();
            }
        }
        return place;
    }

    function p41PlaceFromNominatim(item) {
        var address = (item && item.address) || {};
        return {
            city: address.city || address.town || address.village || address.county || address.state || '',
            countryCode: (address.country_code || '').toUpperCase(),
            country: address.country || ''
        };
    }

    function p41Plain(text) {
        return String(text || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    /* Agreed 12 Oct, decision 1: Where to? is the customer's choice, as in the
     * app - but a choice that plainly contradicts the two addresses earns a
     * question before the price is worked out on it. Returns the question,
     * or '' when nothing looks wrong or the addresses cannot be read. */
    function p41ScopeWarning(scope, from, to) {
        if (!from.countryCode || !to.countryCode) {
            return '';
        }
        if (from.countryCode !== to.countryCode) {
            return scope === 'intercountry' ? '' : "{{ trans('lang.parcel_scope_warning_country') }}";
        }
        if (scope === 'intercountry') {
            return "{{ trans('lang.parcel_scope_warning_same_country') }}";
        }
        if (!from.city || !to.city) {
            return '';
        }
        var sameCity = p41Plain(from.city) === p41Plain(to.city);
        if (scope === 'city' && !sameCity) {
            return "{{ trans('lang.parcel_scope_warning_other_city') }}";
        }
        if (scope === 'intercity' && sameCity) {
            return "{{ trans('lang.parcel_scope_warning_same_city') }}";
        }
        return '';
    }

    function p41PointLabel(point) {
        return [point.name, point.quarter, point.town].filter(Boolean).join(', ');
    }

    /* The pickup points of the customer's region only. */
    (async function loadParcelPickupPoints() {
        try {
            var regionId = await getActiveRegionId();
            if (!regionId) {
                return;
            }
            var snapshot = await database.collection('pickup_points').where('regionId', '==', regionId).get();
            parcelPickupPoints = snapshot.docs.map(function (doc) {
                return Object.assign({id: doc.id}, doc.data());
            }).filter(function (point) {
                return point.publish === true || point.publish === 1 || point.publish === '1';
            }).sort(function (a, b) {
                return p41PointLabel(a).localeCompare(p41PointLabel(b));
            });

            $('.p41-point-select').each(function () {
                var $select = $(this);
                parcelPickupPoints.forEach(function (point) {
                    $select.append($('<option></option>').val(point.id).text(p41PointLabel(point)));
                });
            });
        } catch (err) {
            console.error('pickup points could not be loaded', err);
        }
    })();

    function p41ToggleNoPoints($tab) {
        var wantsPoint = $tab.find('.p41-pickup-method:checked').val() === 'pickup_point'
            || $tab.find('.p41-delivery-method:checked').val() === 'pickup_point';
        $tab.find('.p41-no-points').toggle(wantsPoint && parcelPickupPoints.length === 0);
    }

    $(document).on('change', '.p41-pickup-method', function () {
        var $tab = $(this).closest('.tab-pane');
        $tab.find('.p41-dropoff-point').toggle($(this).val() === 'pickup_point');
        p41ToggleNoPoints($tab);
    });

    $(document).on('change', '.p41-delivery-method', function () {
        var $tab = $(this).closest('.tab-pane');
        $tab.find('.p41-pickup-point').toggle($(this).val() === 'pickup_point');
        p41ToggleNoPoints($tab);
    });

    /* A number of 0 or more, '' when left empty, NaN when it is nonsense. */
    function p41Number(value) {
        var raw = String(value === null || value === undefined ? '' : value).trim().replace(',', '.');
        if (raw === '') {
            return '';
        }
        var number = Number(raw);
        return (isFinite(number) && number >= 0) ? number : NaN;
    }
    
    var currencyData = '';
    var currentCurrency = '';
    var currencyAtRight = false;
    var decimal_degits = 0;

    var refCurrency = regionCurrencyRef();
    refCurrency.get().then(async function (snapshots) {
        currencyData = snapshots.docs[0].data();
        currentCurrency = currencyData.symbol;
        currencyAtRight = currencyData.symbolAtRight;
        if (currencyData.decimal_degits) {
            decimal_degits = currencyData.decimal_degits;
        }
    });
    
    parcelWeightRef.get().then(async function (parcelWeightSnapshots) {
        parcelWeightSnapshots.docs.forEach((listval) => {
            var data = listval.data();
            $('.senderParcelWeight').append($("<option></option>")
                .attr("value", data.id)
                .text(data.title)
                .attr("delivery_charge", data.delivery_charge));
        });
    });
    
    var taxScope = 'order';
    let userCountry = getCookie('userCountryName');
    const scopes = ['order', 'platform'];
    const taxesByScope = {};
    const taxCountryCandidates = (typeof getTaxCountryCandidates === 'function')
        ? getTaxCountryCandidates(userCountry, getCookie('userCountryCode'))
        : [userCountry].filter(Boolean);

    Promise.all(taxCountryCandidates.map(c =>
        database.collection('tax').where('country', '==', c).where('enable', '==', true).where('scope', 'in', scopes).where('sectionId', '==', section_id).get()
    )).then(snapshots => {
        const seenTaxIds = new Set();
        snapshots.forEach(snapshot => {
            snapshot.forEach(doc => {
                if (!seenTaxIds.has(doc.id)) {
                    seenTaxIds.add(doc.id);
                    const tax = doc.data();
                    (taxesByScope[tax.scope] ??= []).push(tax);
                }
            });
        });
    });
    
    var platformCharge = '0';
    let platformFeeSettings = JSON.parse(localStorage.getItem('platformFeeSettings'));
    if (platformFeeSettings && platformFeeSettings.enable) {
        platformCharge = platformFeeSettings.fee;
    }
    
    var address_name = getCookie('address_name');
    let sender_address_lng = getCookie('address_lng');
    let sender_address_lat = getCookie('address_lat');
    let receiver_address_lng = "";
    let receiver_address_lat = "";
    
    if (address_name != undefined) {
        $('.active').find('.senderAddress').val(address_name);
    }
    
    let activeTabId = $('.active').attr('href');
    $(document).on('click', '.nav-item', function () {
        activeTabId = $(this).attr('href');
        $(activeTabId).find('.senderAddress').val(address_name);
    });

    /* ---- Both addresses come from Google (12 Oct) ---------------------------
     *
     * Reported: the receiver's address "is not working"; both addresses should
     * come from Google Maps. Four faults were behind it:
     *
     *  1. The suggestions were attached only when the service_type cookie read
     *     exactly "Parcel Delivery Service" - reach this page any other way and
     *     both boxes were plain text.
     *  2. Choosing a suggestion put only the place's SHORT NAME in the box
     *     ("Ganga Nagar Rd"), and Continue then looked that text up AGAIN - a
     *     bare name often resolves badly or not at all.
     *  3. When that lookup failed, nothing happened. And an EMPTY address was
     *     looked up before it was checked, failed, and also did nothing - so
     *     "please enter the receiver's address" was never shown.
     *  4. Choosing the receiver overwrote the customer's own location
     *     (address_name / address_lat) - and a tab switch then copied the
     *     RECEIVER's name into the SENDER box.
     *
     * Now this page owns its four address boxes: Google suggestions on each,
     * waiting for Maps to finish loading; the FULL address goes in the box;
     * the chosen place's own coordinates and city / country are kept on the
     * box and used as they are. Only an address typed without choosing a
     * suggestion is looked up, and a failed lookup says so.
     * ---------------------------------------------------------------------- */

    /* Read by layouts/footer's setParcelLocations(), which stands down. */
    window.p41ParcelOwnsAddresses = true;

    function p41StoreChosen(input, text, lat, lng, place) {
        $(input).data('p41Chosen', {
            text: String(text || '').trim(),
            lat: parseFloat(lat),
            lng: parseFloat(lng),
            place: place || {city: '', countryCode: '', country: ''}
        });
    }

    /* The place chosen for this box - but only while the box still says what
     * was chosen. Editing the text afterwards means a new address. */
    function p41Chosen(input) {
        var chosen = input ? $(input).data('p41Chosen') : null;
        if (!chosen) {
            return null;
        }
        var current = String($(input).val() || '').trim();
        return (chosen.text === current && isFinite(chosen.lat) && isFinite(chosen.lng)) ? chosen : null;
    }

    $(document).on('input', '.senderAddress, .receiverAddress', function () {
        $(this).removeData('p41Chosen');
    });

    function p41BindAddress(input) {
        if (!input || $(input).data('p41Bound')) {
            return;
        }
        $(input).data('p41Bound', true);

        var autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['formatted_address', 'name', 'geometry', 'address_components']
        });
        autocomplete.addListener('place_changed', function () {
            var place = autocomplete.getPlace();
            var location = place && place.geometry && place.geometry.location;

            /* Enter pressed on typed text: Google returns a place with no
             * geometry. Leave the text; Continue will look it up. */
            if (!location || typeof location.lat !== 'function') {
                $(input).removeData('p41Chosen');
                return;
            }

            var text = place.formatted_address || place.name || '';
            $(input).val(text);
            p41StoreChosen(input, text, location.lat(), location.lng(), p41PlaceFromGoogle(place));
        });
    }

    /* True once done (or when the map is not Google - the footer's own
     * OpenStreetMap suggestions then apply). */
    function p41AttachAddressAutocomplete() {
        /* mapType is read from Firestore AFTER this page starts, so for the
         * first moments it is ''. That means "not known yet" - NOT "not
         * Google". Giving up on '' is exactly why the suggestions never
         * appeared (12 Oct). Only a map type that is SET and is not Google
         * stops this: the footer's OpenStreetMap suggestions then apply. */
        if (typeof mapType !== 'undefined' && mapType && mapType !== 'google') {
            return true;
        }
        if (!(window.google && google.maps && google.maps.places && google.maps.places.Autocomplete)) {
            return false;
        }
        $('.senderAddress, .receiverAddress').each(function () {
            p41BindAddress(this);
        });
        return true;
    }

    /* Maps is loaded only after the map type and its key are read from
     * Firestore, so it is not ready yet. Checked every quarter second for 30
     * seconds - a slow connection can take a while - then left. */
    (function () {
        var tries = 0;
        var timer = setInterval(function () {
            if (p41AttachAddressAutocomplete() || ++tries > 120) {
                clearInterval(timer);
            }
        }, 250);
    })();

    /* One routine for both ends. Calls back only with a usable address. */
    function p41ResolveAddress(role, callback) {
        var isSender = role === 'sender';
        var $input = $(activeTabId).find(isSender ? '.senderAddress' : '.receiverAddress');
        var text = String($input.val() || '').trim();

        var say = function (message) {
            $(".error_top").show().html($('<p></p>').text(message));
            window.scrollTo(0, 0);
        };
        var done = function (lat, lng, place) {
            if (isSender) {
                sender_address_lat = lat;
                sender_address_lng = lng;
                senderPlace = place;
            } else {
                receiver_address_lat = lat;
                receiver_address_lng = lng;
                receiverPlace = place;
            }
            callback(true);
        };
        var notFound = function () {
            say(isSender
                ? "{{ trans('lang.parcel_sender_address_not_found') }}"
                : "{{ trans('lang.parcel_receiver_address_not_found') }}");
        };

        /* Checked FIRST - an empty box used to be looked up, fail, and leave
         * the customer with a button that did nothing. */
        if (text === '') {
            say(isSender ? "{{ trans('lang.sender_address_error') }}" : "{{ trans('lang.receiver_address_error') }}");
            return;
        }

        var chosen = p41Chosen($input[0]);
        if (chosen) {
            done(chosen.lat, chosen.lng, chosen.place);
            return;
        }

        if (mapType == 'google') {
            if (!(window.google && google.maps && google.maps.Geocoder)) {
                notFound();
                return;
            }
            new google.maps.Geocoder().geocode({'address': text}, function (results, status) {
                var first = results && results[0];
                var location = first && first.geometry && first.geometry.location;
                if (status == google.maps.GeocoderStatus.OK && location) {
                    var place = p41PlaceFromGoogle(first);
                    p41StoreChosen($input[0], text, location.lat(), location.lng(), place);
                    done(location.lat(), location.lng(), place);
                } else {
                    notFound();
                }
            });
            return;
        }

        var nominatimUrl = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&q=' + encodeURIComponent(text);
        fetch(nominatimUrl)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.length) {
                    notFound();
                    return;
                }
                /* pick best match with address type */
                var best = data.find(function (item) {
                    return item.type === 'house' || item.type === 'road' || item.type === 'neighbourhood';
                }) || data[0];
                done(parseFloat(best.lat), parseFloat(best.lon), p41PlaceFromNominatim(best));
            })
            .catch(function (error) {
                console.error("Geocoding failed:", error);
                notFound();
            });
    }

    async function getSenderAddress(callback) {
        p41ResolveAddress('sender', callback);
    }

    async function getReceiverAddress(callback) {
        p41ResolveAddress('receiver', callback);
    }

    $(document).on('click', '.parcel_btn', async function () {

        var senderAddress = $(activeTabId).find('.senderAddress').val();
        var senderName = $(activeTabId).find('.senderName').val();
        var senderPhoneCountry = $(activeTabId).find('#senderPhoneCountry').val();
        var senderPhone = $(activeTabId).find('.senderPhone').val();
        var senderParcelWeight = $(activeTabId).find('.senderParcelWeight option:selected').val();
        var senderParcelWeightName = $(activeTabId).find('.senderParcelWeight option:selected').text();
        var delivery_charge = $(activeTabId).find('.senderParcelWeight option:selected').attr('delivery_charge');
        var senderNote = $(activeTabId).find('.senderNote').val();
        var receiverNote = $(activeTabId).find('.receiverNote').val();
        let senderPickupDateTime = "";
        let receiverPickupDateTime = "";
        var receiverAddress = $(activeTabId).find('.receiverAddress').val();
        var receiverName = $(activeTabId).find('.receiverName').val();
        var receiverPhoneCountry = $(activeTabId).find('#receiverPhoneCountry').val();
        var receiverPhone = $(activeTabId).find('.receiverPhone').val();
        var parcelType = $("#parcelType").val();
        var parcelCategoryId = '<?php echo $id; ?>';
        var isSchedule = false;

        /* Point 41 - read from the active tab, like every field above. */
        var $p41Tab = $(activeTabId);
        var shipmentType = $p41Tab.find('.p41-shipment-type:checked').val() || 'parcel';
        var parcelScope = $p41Tab.find('.p41-scope:checked').val() || 'city';
        var weightKg = p41Number($p41Tab.find('.p41-weight-kg').val());
        var dimensions = {};
        $p41Tab.find('.p41-dim').each(function () {
            dimensions[$(this).attr('data-dim')] = p41Number($(this).val());
        });
        var dimensionsGiven = ['l', 'w', 'h'].filter(function (k) { return dimensions[k] !== ''; }).length;
        var declaredValue = p41Number($p41Tab.find('.p41-declared-value').val());
        var contentDescription = String($p41Tab.find('.p41-content-description').val() || '').trim();
        var pickupMethod = $p41Tab.find('.p41-pickup-method:checked').val() || 'home';
        var deliveryMethod = $p41Tab.find('.p41-delivery-method:checked').val() || 'home';
        var dropoffPointId = pickupMethod === 'pickup_point' ? ($p41Tab.find('.p41-dropoff-point').val() || '') : '';
        var pickupPointId = deliveryMethod === 'pickup_point' ? ($p41Tab.find('.p41-pickup-point').val() || '') : '';
        var dropoffPointName = dropoffPointId ? $p41Tab.find('.p41-dropoff-point option:selected').text() : '';
        var pickupPointName = pickupPointId ? $p41Tab.find('.p41-pickup-point option:selected').text() : '';
      
        getSenderAddress(async function (response) {
            getReceiverAddress(async function (response) {

                let sender_zone_id = await getUserZoneId(sender_address_lat, sender_address_lng);
                if (!sender_zone_id) {
                    $(".error_top").show().html("<p>{{ trans('lang.sender_zone_error') }}</p>");
                    window.scrollTo(0, 0);
                    return false;
                }
                let receiver_zone_id = await getUserZoneId(receiver_address_lat, receiver_address_lng);

                if (!receiver_zone_id) {
                    $(".error_top").show().html("<p>{{ trans('lang.receiver_zone_error') }}</p>");
                    window.scrollTo(0, 0);
                    return false;
                }
                
                if (activeTabId == "#schedule") {
                    isSchedule = true;
                    senderPickupDateTime = new Date($(activeTabId).find('.senderArrive').val());
                }
                
                if (senderAddress == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.sender_address_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (senderName == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.sender_name_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (senderPhone == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.sender_phone_error')}}</p>");
                    window.scrollTo(0, 0);
                }else if (!/^\d+$/.test(senderPhone)) {
                    $(".error_top").show().html("<p>{{trans('lang.phone_contains_numbers_only')}}</p>");
                    window.scrollTo(0, 0);
                }else if (senderParcelWeight == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.sender_parcel_weight_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (activeTabId == "#schedule" && senderPickupDateTime == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.when_pickup_address_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (receiverAddress == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.receiver_address_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (receiverName == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.receiver_name_error')}}</p>");
                    window.scrollTo(0, 0);
                } else if (receiverPhone == "") {
                    $(".error_top").show();
                    $(".error_top").html("");
                    $(".error_top").append("<p>{{trans('lang.receiver_phone_error')}}</p>");
                    window.scrollTo(0, 0);
                }else if (!/^\d+$/.test(receiverPhone)) {
                    $(".error_top").show().html("<p>{{trans('lang.phone_contains_numbers_only')}}</p>");
                    window.scrollTo(0, 0);
                } else if (Number.isNaN(weightKg) || Number.isNaN(declaredValue)
                    || ['l', 'w', 'h'].some(function (k) { return Number.isNaN(dimensions[k]); })) {
                    $(".error_top").show().html("<p>{{ trans('lang.parcel_number_error') }}</p>");
                    window.scrollTo(0, 0);
                } else if (dimensionsGiven > 0 && dimensionsGiven < 3) {
                    /* Size counts only with all three - say so, rather than
                       silently ignoring two of them. */
                    $(".error_top").show().html("<p>{{ trans('lang.parcel_dimensions_all_three') }}</p>");
                    window.scrollTo(0, 0);
                } else if (pickupMethod === 'pickup_point' && !dropoffPointId) {
                    $(".error_top").show().html("<p>{{ trans('lang.parcel_choose_dropoff_point') }}</p>");
                    window.scrollTo(0, 0);
                } else if (deliveryMethod === 'pickup_point' && !pickupPointId) {
                    $(".error_top").show().html("<p>{{ trans('lang.parcel_choose_collection_point') }}</p>");
                    window.scrollTo(0, 0);
                } else {

                    var scopeQuestion = p41ScopeWarning(parcelScope, senderPlace, receiverPlace);
                    if (scopeQuestion !== '' && !window.confirm(scopeQuestion)) {
                        return false;
                    }

                    /* The customer's region decides which carriers are offered
                       and the currency of every price (decision 3). */
                    var parcelRegionId = '';
                    try {
                        parcelRegionId = await getActiveRegionId() || '';
                    } catch (err) {
                        console.error('active region could not be read', err);
                    }

                    const payload = {
                            _token: '<?php echo csrf_token(); ?>',
                            section_id,
                            parcelCategoryId,
                            parcelType,
                            isSchedule,
                            senderAddress,
                            senderName,
                            senderPhone: '(+'+senderPhoneCountry+') '+senderPhone,
                            senderParcelWeight,
                            senderParcelWeightName,
                            senderNote,
                            receiverNote,
                            senderPickupDateTime,
                            receiverPickupDateTime,
                            receiverAddress,
                            receiverName,
                            receiverPhone: '(+'+receiverPhoneCountry+') '+receiverPhone,
                            sender_address_lng,
                            sender_address_lat,
                            receiver_address_lng,
                            receiver_address_lat,
                            delivery_charge,
                            discount: 0,
                            parcelImages: JSON.stringify(parcelImages),
                            decimal_degits: decimal_degits,
                            senderZoneId: sender_zone_id,
                            receiverZoneId: receiver_zone_id,
                            mapType: mapType,
                            taxScope,
                            taxesByScope,
                            platformCharge,
                            currencyData,
                            /* Point 41 */
                            regionId: parcelRegionId,
                            shipmentType: shipmentType,
                            scope: parcelScope,
                            weightKg: weightKg === '' ? null : weightKg,
                            dimensions: dimensionsGiven === 3 ? dimensions : null,
                            declaredValue: declaredValue === '' ? '' : String(declaredValue),
                            contentDescription: contentDescription,
                            pickupMethod: pickupMethod,
                            deliveryMethod: deliveryMethod,
                            dropoffPointId: dropoffPointId,
                            pickupPointId: pickupPointId,
                            dropoffPointName: dropoffPointName,
                            pickupPointName: pickupPointName,
                            origin: senderPlace,
                            destination: receiverPlace,
                    };

                    $.ajax({
                        type: 'POST',
                        url: "<?php echo route('parcel_cart'); ?>",
                        contentType: 'application/json',
                        dataType: 'json',
                        data: JSON.stringify(payload),
                        success: function (data) {
                            /* Point 41: e.g. "Other country" in a region where
                               no carrier delivers abroad. */
                            if (data && data.status === false) {
                                $(".error_top").show().html($('<p></p>').text(data.message || ''));
                                window.scrollTo(0, 0);
                                return;
                            }
                            var url = "{{ route('parcel_checkout')}}";
                            window.location.href = url;
                        }
                    });
                }
            });
        });
    });
    var storageRef = firebase.storage().ref('images');

    function handleFileSelect(evt) {
        var f = evt.target.files[0];
        var reader = new FileReader();
        reader.onload = (function (theFile) {
            return function (e) {
                var filePayload = e.target.result;
                var hash = CryptoJS.SHA256(Math.random() + CryptoJS.SHA256(filePayload));
                var val = f.name;
                var ext = val.split('.')[1];
                var docName = val.split('fakepath')[1];
                var filename = (f.name).replace(/C:\\fakepath\\/i, '')
                var timestamp = Number(new Date());
                var filename = filename.split('.')[0] + "_" + timestamp + '.' + ext;
                var uploadTask = storageRef.child(filename).put(theFile);
                uploadTask.on('state_changed', function (snapshot) {
                    var progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
                    console.log('Upload is ' + progress + '% done');
                    $(activeTabId).find(".uploding_image_photos").text("Image is uploading...");
                }, function (error) {
                }, function () {
                    uploadTask.snapshot.ref.getDownloadURL().then(function (downloadURL) {
                        $(activeTabId).find(".uploding_image_photos").text("Upload is completed");
                        if (downloadURL) {
                            parcelImagesCount++;
                            photos_html = '<div id="parcelPhotos"><span class="image-item" id="photo_' + parcelImagesCount + '"><span class="remove-btn" data-id="' + parcelImagesCount + '" data-img="' + downloadURL + '"><i class="fa fa-remove"></i></span><img width="100px" id="" height="auto" src="' + downloadURL + '"></span></div>';
                            $(activeTabId).find(".appendParcelImages").append(photos_html);
                            parcelImages.push(downloadURL);
                        }
                    });
                });
            };
        })(f);
        reader.readAsDataURL(f);
    }

    $(document).on("click", ".remove-btn", function () {
        var id = $(this).attr('data-id');
        var photo_remove = $(this).attr('data-img');
        $("#photo_" + id).remove();
        index = parcelImages.indexOf(photo_remove);
        if (index > -1) {
            parcelImages.splice(index, 1);
        }
    });

    var newcountriesjs = '<?php echo json_encode($newcountriesjs); ?>';
    var newcountriesjs = JSON.parse(newcountriesjs);
    
    function formatState(state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "<?php echo URL::to('/'); ?>/flags/120/";
        var $state = $(
            '<span><img src="' + baseUrl + '/' + newcountriesjs[state.element.value].toLowerCase() + '.png" class="img-flag" /> ' + state.text + '</span>'
        );
        return $state;
    }

    function formatState2(state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "<?php echo URL::to('/'); ?>/flags/120/"
        var $state = $(
            '<span><img class="img-flag" /> <span></span></span>'
        );
        $state.find("span").text(state.text);
        $state.find("img").attr("src", baseUrl + "/" + newcountriesjs[state.element.value].toLowerCase() + ".png");
        return $state;
    }

    jQuery(".country_selector").select2({
        templateResult: formatState,
        templateSelection: formatState2,
        placeholder: "Select Country",
        allowClear: true
    });
    // --- ADD THIS BLOCK TO SET DEFAULT COUNTRY CODE ---
    var globalSettingsRef = database.collection('settings').doc('globalSettings');
    
    globalSettingsRef.get().then(async function (snapshot) {
        var globalSettings = snapshot.data();
       
        if (globalSettings && globalSettings.defaultCountryCode) {
            var defaultPhoneCode = globalSettings.defaultCountryCode.replace('+', '').trim();

            // Find the option with matching phoneCode
            var $option = $(".country_selector option").filter(function() {
                return $(this).val() === defaultPhoneCode;
            });

            if ($option.length > 0) {
                $(".country_selector").val(defaultPhoneCode).trigger('change');
            } else {
                console.warn("Default country code not found in list:", defaultPhoneCode);
            }
        }
    }).catch(function (error) {
        console.error("Error fetching global settings: ", error);
    });
    // --- END OF DEFAULT COUNTRY LOGIC ---

    $(document).ready(function(){
        let now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        let minDateTime = now.toISOString().slice(0,16);
        $(".senderArrive").attr("min", minDateTime);
    });

</script>

@include('layouts.nav')
