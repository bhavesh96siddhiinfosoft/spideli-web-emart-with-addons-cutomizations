@include('layouts.app')
@include('layouts.header')
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
    <div class="rental-login">
        <div class="rental-login-inner">
            <div class="container">
                <div class="rental-login-form">
                    <h3 class="text-center">{{trans('lang.luxury_car_rental')}}</h3>
                    <div class="rental-login-form-inner">
                        <div class="row align-items-center form-row">
                            <div class="col-sm-12">
                                <label>{{trans('lang.pickup_location')}}</label>
                                <input type="text" class="form-control pickLocation" id="pickLocation" placeholder="Pick-up location" onchange="pickLocation()">
                            </div>
                        </div>
                        <div class="row align-items-center form-row">
                            <div class="col-sm-12">
                                <label>{{trans('lang.select_date')}}</label>
                                <input type="text" name="driverDates" class="form-control driverDates">
                            </div>
                        </div>
                        <div class="row align-items-center form-row">
                            <div class="col-sm-12">
                                <div class="vehicle-selection">
                                    <label>{{trans('lang.select_vehicle_type')}}</label>
                                    <select name="vehicle_type" id="vehicle_type" class="form-control">
                                        <option value="">{{ trans('lang.select_vehicle_type')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center form-row">
                            <div class="col-sm-12">
                                <div class="vehicle-selection">
                                    <label>{{trans('lang.select_rental_package')}}</label>
                                    <select name="rental_package" id="rental_package" class="form-control">
                                        <option value="">{{ trans('lang.select_rental_package')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        {{-- #112: number of days for a package by the day, and a note for any rental --}}
                        <div class="row align-items-center form-row spd-rental-days-row" style="display:none">
                            <div class="col-sm-12">
                                <label for="rental_days">{{trans('lang.rental_number_of_days')}} <small class="text-muted" id="rental_days_range"></small></label>
                                <input type="number" class="form-control" id="rental_days" min="1" step="1" value="1" inputmode="numeric">
                                <small class="form-text text-muted" id="rental_days_summary"></small>
                            </div>
                        </div>
                        <div class="row align-items-center form-row">
                            <div class="col-sm-12">
                                <label for="rental_note">{{trans('lang.rental_booking_note')}}</label>
                                <textarea class="form-control" id="rental_note" rows="2" maxlength="500" placeholder="{{trans('lang.rental_booking_note_placeholder')}}"></textarea>
                            </div>
                        </div>
                        <div class="row align-items-center form-row drop-check">
                            <div class="col-sm-12">
                                <div class="form-check">
                                    <span class="text-danger noData"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center form-row form-btn">
                            <div class="col-sm-12">
                                <button type="button" class="btn btn-primary" id="find_car">{{trans('lang.find_car')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.footer')

<script src="{{ asset('js/geofirestore.js') }}"></script>
<script src="https://cdn.firebase.com/libs/geofire/5.0.1/geofire.min.js"></script>
<script type="text/javascript" src="{{asset('vendor/slick/slick.min.js')}}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>

<script type="text/javascript">

    var firestore = firebase.firestore();
    var geoFirestore = new GeoFirestore(firestore);
    var database = firebase.firestore();

    $('input[name="driverDates"]').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: false,
        timePickerIncrement: 5,
        minDate: new Date(),
        locale: {
            format: 'YYYY-MM-DD hh:mm A'
        },
    });

    var section_id = getCookie('section_id');
    let userCountry = getCookie('userCountryName');

    var taxScope = 'order';
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
    
    var adminCommission = '';
    var commissionType = '';
    var adminCommissionRef = database.collection('sections').where('id', '==', section_id);
    adminCommissionRef.get().then(async function (AdminCommissionSnapshots) {
        if (AdminCommissionSnapshots.docs.length > 0) {
            AdminCommissionRes = AdminCommissionSnapshots.docs[0].data();
            var data = await sectionCommissionEffective(AdminCommissionRes.adminCommision);
            if (data.enable) {
                adminCommission = data.commission;
                commissionType = data.type;
            }
        }
    });

    var currencyData = '';
    var decimal_degits = 0;
    var currentCurrency = '';
    var currencyAtRight = false;
    var refCurrency = regionCurrencyRef();
    refCurrency.get().then(async function (snapshots) {
        currencyData = snapshots.docs[0].data();
        currentCurrency = currencyData.symbol;
        currencyAtRight = currencyData.symbolAtRight;
        if (currencyData.decimal_degits) {
            decimal_degits = currencyData.decimal_degits;
        }
    });

    var placeholderImageRef = database.collection('settings').doc('placeHolderImage');
    var placeholderImageSrc = '';
    placeholderImageRef.get().then(async function(placeholderImageSnapshots) {
        var placeHolderImageData = placeholderImageSnapshots.data();
        placeholderImageSrc = placeHolderImageData.image;
    })

    var address_lat = "";
    var address_lng = "";
    
    $('#vehicle_type').select2();
    $('#rental_package').select2();
    
    var rentalVehicleTypeRef = database.collection('rental_vehicle_type').where('sectionId', '==', section_id).where('isActive','==',true);
    rentalVehicleTypeRef.get().then(async function (snapshots) {
        if (snapshots.docs.length > 0) {
             snapshots.docs.forEach((listval) => {
                var data = listval.data();
                $('#vehicle_type').append(
                    $("<option></option>")
                        .attr("value", data.id)
                        .attr("data-image", data.rental_vehicle_icon)
                        .attr("data-description", data.short_description)
                        .attr("data-vehicle", JSON.stringify(data))
                        .text(data.name)
                );
            });
            $('#vehicle_type').select2({
                templateResult: formatOption,
                templateSelection: formatSelection
            });
        }
    });

    $(document).on('change', '#vehicle_type', function () {
        let vehicleTypeId = $(this).val();
        var rentalVehicleTypeRef = database.collection('rental_packages').where('vehicleTypeId','==',vehicleTypeId).where('published','==',true);
        rentalVehicleTypeRef.get().then(async function (snapshots) {
            $('#rental_package').empty().append("<option value=''>{{ trans('lang.select_rental_package')}}</option>");
            spideliRentalDaysUI();
            if (snapshots.docs.length > 0) {
                snapshots.docs.forEach((listval) => {
                    var data = listval.data();
                    $('#rental_package').append(
                        $("<option></option>")
                            .attr("value", data.id)
                            .attr("data-description", data.description)
                            .attr("data-price", data.baseFare)
                            .attr("data-price-format", getProductFormattedPrice(parseFloat(data.baseFare)) + (data.packageType === 'daily' ? ' ' + spdRentalText.perDay : ''))
                            .attr("data-package", JSON.stringify(data))
                            .text(data.name)
                    );
                });
                $('#rental_package').select2({
                    templateResult: formatOption,
                    templateSelection: formatSelection
                });
            }
        });
    });

    // Format dropdown list options
    function formatOption(option) {
        if (!option.id) return option.text;
        var image = $(option.element).data('image');
        var desc  = $(option.element).data('description');
        var price  = $(option.element).data('price-format');
        var name  = option.text;
        var imageHtml = image 
            ? `<img src="${image}" onerror="this.onerror=null;this.src='${placeholderImageSrc}'" style="width:40px; height:40px; object-fit:cover; border-radius:4px;" />` 
            : "";
        return $(
            `<div class="vehicle-option">
                <div class="vehicle-list-left">
                    ${imageHtml}
                    <div class="vehicle-detail">
                        <div class="vehicle-name">${name}</div>
                        ${ desc ? `<div class="vehicle-description">${desc}</div>` : ""}
                     </div>
                </div>
                ${ price ? `<div class="vehicle-price">${price}</div>` : ""}
            </div>`
        );
    }

    // Format selected value (top input area)
    function formatSelection(option) {
        if (!option.id) return option.text;
        var image = $(option.element).data('image');
        var name  = option.text;
        var imageHtml = image 
            ? `<img src="${image}" onerror="this.onerror=null;this.src='${placeholderImageSrc}'" style="width:24px; height:24px; object-fit:cover; border-radius:3px;" />` 
            : "";
        return $(
            `<div class="vehicle-option">
                ${imageHtml}
                <span>${name}</span>
            </div>`
        );
    }

    $(document).on('change', '.isDropSameLocation', function () {
        if ($(this).is(':checked') == true) {
            $('.dropOffDiv').hide();
        } else {
            $('.dropOffDiv').show();
        }
    });
    
    // #112: rental by the day
    var spdRentalText = {
        perDay: @json(trans('lang.rental_per_day')),
        returnDate: @json(trans('lang.rental_return_date')),
        range: @json(trans('lang.rental_days_range')),
        invalid: @json(trans('lang.rental_days_invalid'))
    };

    function spideliSelectedPackage() {
        var raw = $('#rental_package option:selected').attr('data-package');
        if (!raw) return null;
        try { return JSON.parse(raw); } catch (e) { return null; }
    }

    function spideliRentalDayLimits(pkg) {
        var min = parseInt(pkg && pkg.minDays, 10), max = parseInt(pkg && pkg.maxDays, 10);
        if (!(min >= 1)) min = 1;
        if (!(max >= min)) max = Math.max(min, 365);
        return {min: min, max: Math.min(max, 365)};
    }

    // Returns the number of days, or null when the value is not a whole number within the package's limits.
    function spideliRentalDays(pkg) {
        var text = String($('#rental_days').val() || '').trim();
        if (!/^\d{1,3}$/.test(text)) return null;
        var days = parseInt(text, 10), lim = spideliRentalDayLimits(pkg);
        return (days >= lim.min && days <= lim.max) ? days : null;
    }

    function spideliRentalDaysUI() {
        var pkg = spideliSelectedPackage();
        if (!pkg || pkg.packageType !== 'daily') {
            $('.spd-rental-days-row').hide();
            return;
        }
        var lim = spideliRentalDayLimits(pkg);
        $('#rental_days').attr({min: lim.min, max: lim.max});
        $('#rental_days_range').text('(' + spdRentalText.range.replace(':min', lim.min).replace(':max', lim.max) + ')');
        var days = spideliRentalDays(pkg);
        var summary = '';
        if (days !== null) {
            var dayFare = parseFloat(pkg.baseFare) || 0;
            summary = getProductFormattedPrice(dayFare) + ' × ' + days + ' = ' + getProductFormattedPrice(dayFare * days);
            var start = moment($('.driverDates').val(), 'YYYY-MM-DD hh:mm A');
            if (start.isValid()) {
                summary += ' · ' + spdRentalText.returnDate + ': ' + start.clone().add(days * 24, 'hours').format('DD MMM YYYY, hh:mm A');
            }
        } else {
            summary = spdRentalText.invalid;
        }
        $('#rental_days_summary').text(summary);
        $('.spd-rental-days-row').show();
    }

    $(document).on('change', '#rental_package', function () {
        var pkg = spideliSelectedPackage();
        if (pkg && pkg.packageType === 'daily') {
            $('#rental_days').val(spideliRentalDayLimits(pkg).min);
        }
        spideliRentalDaysUI();
    });
    $(document).on('input change', '#rental_days', spideliRentalDaysUI);
    $('input[name="driverDates"]').on('apply.daterangepicker change', spideliRentalDaysUI);

    $(document).on('click', '#find_car', async function () {
        
        var platformCharge = '0';
        let platformFeeSettings = JSON.parse(localStorage.getItem('platformFeeSettings'));
        if (platformFeeSettings && platformFeeSettings.enable) {
            platformCharge = platformFeeSettings.fee;
        }
        
        var startDate = $('.driverDates').val();
        var pickLocation = $('.pickLocation').val();
        var vehicleTypeId = $('#vehicle_type').val();
        var rentalPackageId = $('#rental_package').val();
        var user_zone_id = await getUserZoneId();
       
        $('.noData').show();
        $('.noData').html("");

        if (pickLocation == "") {
            $('.noData').html("{{trans('lang.pickup_location_error')}}");
            window.scroll(0, 0);
        }else if (vehicleTypeId == "") {
            $('.noData').html("{{trans('lang.vehicle_type_error')}}");
            window.scroll(0, 0);            
        }else if (rentalPackageId == "") {
            $('.noData').html("{{trans('lang.rental_package_error')}}");
            window.scroll(0, 0);            
        }else if (spideliSelectedPackage() && spideliSelectedPackage().packageType === 'daily' && spideliRentalDays(spideliSelectedPackage()) === null) {
            $('.noData').html(spdRentalText.invalid);
            window.scroll(0, 0);
        }else if (user_zone_id == null) {
            $('.noData').html("{{trans('lang.user_zone_error')}}");
            window.scroll(0, 0);            
        } else {

            $('.noData').hide();
            $('.noData').html("");
            var baseFarePrice = $('#rental_package option:selected').data('price');
            var rentalVehicleType = JSON.parse($('#vehicle_type option:selected').attr('data-vehicle'));
            var rentalPackageModel = JSON.parse($('#rental_package option:selected').attr('data-package'));

            const payload = {
                    _token: '<?php echo csrf_token(); ?>',
                    startDate,
                    pickLocation,
                    address_lat,
                    address_lng,
                    vehicleTypeId,
                    rentalPackageId,
                    rentalPackageModel,
                    rentalVehicleType,
                    baseFarePrice,
                    adminCommissionType: commissionType,
                    adminCommission,
                    decimal_degits,
                    zoneId:user_zone_id,
                    taxScope,
                    taxesByScope,
                    platformCharge,
                    currencyData,
                    rentalDays: (rentalPackageModel.packageType === 'daily') ? spideliRentalDays(rentalPackageModel) : null,
                    bookingNote: String($('#rental_note').val() || '').trim().substring(0, 500),
                };

             $.ajax({
                type: 'POST',
                url: "<?php echo route('find_rental_cars'); ?>",
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(payload),
                success: function (data) {
                    if (data && data.status === false) {   // #112: refused day count
                        $('.noData').show().text(data.message || spdRentalText.invalid);
                        window.scroll(0, 0);
                        return;
                    }
                    var url = "{{route('rental_cars_checkout')}}";
                    window.location.href = url;
                }
            });
        }
    });
</script>
@include('layouts.nav')
