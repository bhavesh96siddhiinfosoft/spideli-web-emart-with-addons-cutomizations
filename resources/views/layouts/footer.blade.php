<button type="button" id="locationModal" data-toggle="modal" data-target="#locationModalAddress" hidden>submit</button>
<div class="modal fade" id="locationModalAddress" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered location_modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title locationModalTitle">{{ trans('lang.delivery_address') }}</h5>
            </div>
            <div class="modal-body">
                <form class="">
                    <div class="form-row">
                        <div class="col-md-12 form-group">
                            <label class="form-label">{{ trans('lang.street_1') }}</label>
                            <div class="input-group">
                                <input placeholder="Delivery Area" type="text" id="address_line1" class="form-control">
                                <div class="input-group-append">
                                    <button id="use_my_location" onclick="getCurrentLocationAddress1()" type="button" class="btn btn-outline-secondary" title="{{ trans('lang.use_my_location') }}"><i class="feather-map-pin"></i></button>
                                </div>
                            </div>
                            <div id="address_modal_status" class="form-text text-muted" style="display:none;"></div>
                        </div>
                        <div class="col-md-12 form-group"><label class="form-label">{{ trans('lang.landmark') }}</label><input placeholder="{{ trans('lang.footer') }}" value="" id="address_line2" type="text" class="form-control"></div>
                        <div class="col-md-12 form-group"><label class="form-label">{{ trans('lang.zip_code') }}</label><input placeholder="{{ trans('lang.postalcode') }}" id="address_zipcode" type="text" class="form-control"></div>
                        <div class="col-md-12 form-group"><label class="form-label">{{ trans('lang.city') }}</label><input placeholder="{{ trans('lang.city') }}" id="address_city" type="text" class="form-control"></div>
                        <div class="col-md-12 form-group"><label class="form-label">{{ trans('lang.country') }}</label><input placeholder="{{ trans('lang.country') }}" id="address_country" type="text" class="form-control">
                        </div>
                        <input type="hidden" name="address_lat" id="address_lat">
                        <input type="hidden" name="address_lng" id="address_lng">
                    </div>
                </form>
            </div>
            <div class="modal-footer p-0 border-0">
                <div class="col-12 m-0 p-0">
                    <button type="button" id="close_button" class="close" data-dismiss="modal" aria-label="Close" hidden></button>
                    <button type="button" class="btn btn-primary btn-lg btn-block" onclick="saveShippingAddress()">{{ trans('lang.save_changes') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<span style="display: none;">
    <button type="button" class="btn btn-primary" id="order_notification_modal" data-toggle="modal" data-target="#order_notification">{{ trans('lang.large_modal') }}</button>
</span>
<div class="modal fade" id="order_notification" tabindex="-1" role="dialog" aria-labelledby="notification_accepted_order_by_vendor" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered notification-main" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title order_notification_title" id="exampleModalLongTitle"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6><span id="restaurnat_name" class="order_notification_message"></span></h6>
            </div>
            <div class="modal-footer">
                <?php if (@$_COOKIE['service_type'] == "Parcel Delivery Service") { ?>
                <button type="button" class="btn btn-primary"><a href="{{ route('parcel_orders') }}" id="order_notification_url">{{ trans('lang.Go') }}</a>
                </button>
                <?php } else if (@$_COOKIE['service_type'] == "Rental Service") { ?>
                <button type="button" class="btn btn-primary"><a href="{{ route('rental_orders') }}" id="order_notification_url">{{ trans('lang.Go') }}</a>
                </button>
                <?php } else { ?>
                <button type="button" class="btn btn-primary"><a href="{{ route('my_order') }}" id="order_notification_url">{{ trans('lang.Go') }}</a>
                </button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<span style="display: none;">
    <button type="button" class="btn btn-primary" id="dinein_order_notification_modal" data-toggle="modal" data-target="#dinein_order_notification">{{ trans('lang.large_modal') }}</button>
</span>
<div class="modal fade" id="dinein_order_notification" tabindex="-1" role="dialog" aria-labelledby="notification_accepted_order_by_vendor" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered notification-main" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5 class="modal-title dinein_order_notification_title" id="exampleModalLongTitle"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6><span id="restaurnat_name" class="dinein_order_notification_message"></span></h6>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary"><a href="{{ url('my_dinein') }}" id="dinein_order_notification_url">{{ trans('lang.go') }}</a>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Store Select Model -->
<div class="modal fade" id="select_store_model" tabindex="-1" role="dialog" aria-hidden="true">
    {{-- Left CENTRED, as it always was. Bootstrap's modal-dialog-scrollable
         was tried here and is wrong alongside modal-dialog-centered: the two
         together force the dialog to exactly calc(100% - 1rem), so it fills
         the screen top to bottom and looks stuck to the bottom edge.

         The list is capped and scrolled in the body instead - see the style
         below - which keeps the window the size it has always been. --}}
    <div class="modal-dialog modal-dialog-centered notification-main" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h5>{{ trans('lang.select_sections') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="section_list row mt-3" id="section_lists"></div>
            </div>
            <style>
                /* Grouping added five headings on top of twelve tiles, so the
                   list outgrew the window. The BODY scrolls; the heading and
                   the close button stay put. 65vh leaves room for the header
                   and a margin at both ends on any screen. */
                #select_store_model .modal-body {
                    max-height: 65vh;
                    overflow-y: auto;
                }
            </style>
        </div>
    </div>
</div>
<footer class="section-footer border-top bg-dark">
    <div class="footerTemplate"></div>
    <div class="select-sec-btn">
        <a href="#" data-toggle="modal" id="select_store_model_call" data-target="#select_store_model">{{ trans('lang.select_section') }}</a>
    </div>
</footer>
<script type="text/javascript" src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script type="text/javascript" src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<?php if (str_replace('_', '-', app()->getLocale()) == 'ar') { ?>
<script type="text/javascript" src="{{ asset('vendor/bootstrap/js/bootstrap-rtl.bundle.min.js') }}"></script>
<?php } ?>
<script type="text/javascript" src="{{ asset('vendor/sidebar/hc-offcanvas-nav.js') }}"></script>
<script type="text/javascript" src="{{ asset('vendor/slick/slick.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('vendor/slick/slick-lightbox.js') }}"></script>
<script src="{{ asset('vendor/select2/dist/js/select2.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/siddhi.js') }}"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-firestore-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-storage-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"></script>
<script src="{{ asset('js/crypto-js.js') }}"></script>
<script src="{{ asset('js/jquery.cookie.js') }}"></script>
<script src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">

    var database = firebase.firestore();
    <?php $id = null;
    if (Auth::user()) {
        $id = Auth::user()->getvendorId();
    } ?>
    
    var cuser_id = '<?php echo $id; ?>';
    var dine_in_enable = false;
    var place = [];
    var address_name = getCookie('address_name');
    var address_name1 = getCookie('address_name1');
    var address_name2 = getCookie('address_name2');
    var address_zip = getCookie('address_zip');
    var address_lat = getCookie('address_lat');
    var address_lng = getCookie('address_lng');
    var address_city = getCookie('address_city');
    var address_state = getCookie('address_state');
    var address_country = getCookie('address_country');
    var googleMapKey = '';
    var mapType = '';
    var type = '';
    var mapTypeDoc = database.collection('settings').doc('DriverNearBy');
    mapTypeDoc.get().then(async function(snapshots) {
        var mapTypeData = snapshots.data();

        /* No settings document leaves mapType as '', which every reader below
         * already treats as "not google" - so the map still loads. */
        if (mapTypeData) {
            mapType = mapTypeData.selectedMapType;
        }
    })

    var invalidUserIds = [];
    const BATCH_SIZE = 100;
    const MAX_PARALLEL_BATCHES = 5;
    
    async function getInvaidUserIds() {
        if (getCookie('section_id') != null && getCookie('section_id') != "" && getCookie('section_id') != undefined && getCookie('section_name') != null && getCookie('section_name') != "" && getCookie('section_name') != undefined) {
            var section_id = getCookie('section_id');
            var subscriptionModel = false;
            var businessModel = database.collection('settings').doc("vendor");
            await businessModel.get().then(async function(snapshots) {
                var businessModelSettings = snapshots.data();

                /* AWAITED, so a throw here rejects the caller rather than
                 * staying in this promise - and the caller decides which
                 * stores and services a visitor may see. */
                if (businessModelSettings && businessModelSettings.hasOwnProperty('subscription_model') && businessModelSettings.subscription_model == true) {
                    subscriptionModel = true;
                }
            });
            var commisionModel = false;
            var commissionModel = database.collection('sections').doc(section_id);
            await commissionModel.get().then(async function(snapshots) {
                var commissionSetting = snapshots.data();
                if (commissionSetting && commissionSetting.adminCommision && commissionSetting.adminCommision.enable) {
                    commisionModel = true;
                }
            });
            if (subscriptionModel || commisionModel) {
                var role = getCookie('service_type') == 'On Demand Service' ? 'provider' : 'vendor';
                let vendorSnapshots = '';
                if (role == 'provider') {
                    vendorSnapshots = await database.collection('users').where('role', '==', role).limit(BATCH_SIZE).get();
                } else {
                    vendorSnapshots = await database.collection('vendors').where('section_id', '==', section_id).limit(BATCH_SIZE).get();
                }
                let batchPromises = [];
                while (!vendorSnapshots.empty) {
                    const vendorPromise = processBatch(vendorSnapshots, invalidUserIds, role);
                    batchPromises.push(vendorPromise);
                    if (batchPromises.length >= MAX_PARALLEL_BATCHES) {
                        await Promise.race(batchPromises);
                    }
                    if (role == 'provider') {
                        vendorSnapshots = await database.collection('users').where('role', '==', role).startAfter(vendorSnapshots.docs[vendorSnapshots.docs.length - 1]).limit(BATCH_SIZE).get();
                    } else {
                        vendorSnapshots = await database.collection('vendors').where('section_id', '==', section_id).startAfter(vendorSnapshots.docs[vendorSnapshots.docs.length - 1]).limit(BATCH_SIZE).get();
                    }
                }
                await Promise.all(batchPromises);
            }
            return invalidUserIds;
        }

        /* Without this the function returns undefined whenever the section
         * cookies are not both set, and every caller then does
         * inValidVendors.includes(...) on undefined - which throws and
         * leaves the store listings empty. Always hand back an array. */
        return invalidUserIds;
    }
    async function processBatch(vendorSnapshots, invalidUserIds, role) {
        const vendorPromises = vendorSnapshots.docs.map(vendorDoc => processVendor(vendorDoc, invalidUserIds, role));
        return Promise.all(vendorPromises);
    }
    async function processVendor(vendorDoc, invalidUserIds, role) {
        var userData = vendorDoc.data();
        if (userData.hasOwnProperty('subscriptionPlanId') && userData.subscriptionPlanId != '' && userData.subscriptionPlanId != null) {
            if (userData.subscriptionExpiryDate && userData.subscriptionExpiryDate != null) {
                const subscriptionExpiryDate = userData.subscriptionExpiryDate;
                if (subscriptionExpiryDate && new Date(subscriptionExpiryDate.seconds * 1000) < new Date()) {
                    (role == 'provider') ? invalidUserIds.push(userData.id): invalidUserIds.push(userData.author);
                    return;
                }
            }
            const orderCount = userData.subscriptionTotalOrders
            const orderLimit = userData.subscription_plan ? userData.subscription_plan.orderLimit : 0;
            if (orderLimit != '-1') {
                if (parseInt(orderCount) == 0) {
                    (role == 'provider') ? invalidUserIds.push(userData.id): invalidUserIds.push(userData.author);
                }
            }
        } else {
            (role == 'provider') ? invalidUserIds.push(userData.id): invalidUserIds.push(userData.author);
        }
    }
    async function getUserItemLimit(userId) {
        let inValidProductIds = [];
        let section_id = getCookie('section_id');
        let section_name = getCookie('section_name');
        if (!section_id || !section_name) return inValidProductIds; // Exit early if section data is missing
        let [businessModelSnap, commissionModelSnap] = await Promise.all([
            database.collection('settings').doc("vendor").get(),
            database.collection('sections').doc(section_id).get()
        ]);
        let businessModelSettings = businessModelSnap.data();
        let commissionSetting = commissionModelSnap.data();
        let subscriptionModel = businessModelSettings?.subscription_model ?? false;
        let commisionModel = commissionSetting?.adminCommision?.enable ?? false;
        if (!subscriptionModel && !commisionModel) return inValidProductIds; // Exit early if neither model applies
        let vendorSnap = await database.collection('vendors').where('id', '==', userId).get();
        if (vendorSnap.empty) return inValidProductIds;
        let vendorData = vendorSnap.docs[0].data();
        if (vendorData.hasOwnProperty('subscription_plan') && vendorData.subscription_plan != null && vendorData.subscription_plan != '') {
            let itemLimit = vendorData?.subscription_plan?.itemLimit ?? -1;
            itemLimit = parseInt(itemLimit);
            if (parseInt(vendorData.subscriptionTotalOrders) == 0) {
                let inValidProductsSnap = await database.collection('vendor_products')
                    .where('vendorID', '==', userId)
                    .get();
                inValidProductIds = inValidProductsSnap.docs.map(doc => doc.id);
            } else if (vendorData.subscriptionExpiryDate && vendorData.subscriptionExpiryDate != null) {
                const subscriptionExpiryDate = vendorData.subscriptionExpiryDate;
                if (subscriptionExpiryDate && new Date(subscriptionExpiryDate.seconds * 1000) < new Date()) {
                    let inValidProductsSnap = await database.collection('vendor_products')
                        .where('vendorID', '==', userId)
                        .get();
                    inValidProductIds = inValidProductsSnap.docs.map(doc => doc.id);
                }
            }
            if (inValidProductIds.length == 0) {
                if (parseInt(itemLimit) != -1) {
                    let validProductsSnap = await database.collection('vendor_products')
                        .where('vendorID', '==', userId)
                        .orderBy('createdAt', 'asc')
                        .limit(itemLimit)
                        .get();
                    if (!validProductsSnap.empty) {
                        let lastDoc = validProductsSnap.docs[validProductsSnap.docs.length - 1];
                        let inValidProductsSnap = await database.collection('vendor_products')
                            .where('vendorID', '==', userId)
                            .orderBy('createdAt', 'asc')
                            .startAfter(lastDoc)
                            .get();
                        inValidProductIds = inValidProductsSnap.docs.map(doc => doc.id);
                    }
                }
            }
        } else {
            let inValidProductsSnap = await database.collection('vendor_products')
                .where('vendorID', '==', userId)
                .get();
            inValidProductIds = inValidProductsSnap.docs.map(doc => doc.id);
        }
        return inValidProductIds;
    }
    async function getProviderServiceLimit(userId) {
        var inValidServiceIds = [];
        if (getCookie('section_id') != null && getCookie('section_id') != "" && getCookie('section_id') != undefined && getCookie('section_name') != null && getCookie('section_name') != "" && getCookie('section_name') != undefined) {
            var section_id = getCookie('section_id');
            var subscriptionModel = false;
            var businessModel = database.collection('settings').doc("vendor");
            await businessModel.get().then(async function(snapshots) {
                var businessModelSettings = snapshots.data();

                /* AWAITED, so a throw here rejects the caller rather than
                 * staying in this promise - and the caller decides which
                 * stores and services a visitor may see. */
                if (businessModelSettings && businessModelSettings.hasOwnProperty('subscription_model') && businessModelSettings.subscription_model == true) {
                    subscriptionModel = true;
                }
            });
            var commisionModel = false;
            var commissionModel = database.collection('sections').doc(section_id);
            await commissionModel.get().then(async function(snapshots) {
                var commissionSetting = snapshots.data();
                if (commissionSetting && commissionSetting.adminCommision && commissionSetting.adminCommision.enable) {
                    commisionModel = true;
                }
            });
            if (subscriptionModel || commisionModel) {
                await database.collection('users').where('id', '==', userId).get().then(async function(snapshot) {
                    if (snapshot.docs.length > 0) {
                        var data = snapshot.docs[0].data();
                        if (data.hasOwnProperty('subscription_plan') && data.subscription_plan != null && data.subscription_plan != '') {
                            var itemLimit = data.subscription_plan.itemLimit;
                            if (parseInt(data.subscriptionTotalOrders) == 0) {
                                var refInValidServices = await database.collection('providers_services').where('author', '==', userId).get();
                                refInValidServices.forEach(doc => {
                                    inValidServiceIds.push(doc.id);
                                });
                            } else if (data.subscriptionExpiryDate && data.subscriptionExpiryDate != null) {
                                const subscriptionExpiryDate = data.subscriptionExpiryDate;
                                if (subscriptionExpiryDate && new Date(subscriptionExpiryDate.seconds * 1000) < new Date()) {
                                    var refInValidServices = await database.collection('providers_services').where('author', '==', userId).get();
                                    refInValidServices.forEach(doc => {
                                        inValidServiceIds.push(doc.id);
                                    });
                                }
                            }
                            if (inValidServiceIds.length == 0) {
                                if (parseInt(itemLimit) != -1) {
                                    var refValidServices = await database.collection('providers_services').where('author', '==', userId).orderBy('createdAt', 'asc').limit(parseInt(itemLimit)).get();
                                    if (!refValidServices.empty) {
                                        let lastDoc = refValidServices.docs[refValidServices.docs.length - 1];
                                        var refInValidServices = await database.collection('providers_services')
                                            .where('author', '==', userId)
                                            .orderBy('createdAt', 'asc')
                                            .startAfter(lastDoc)
                                            .get();
                                        refInValidServices.forEach(doc => {
                                            inValidServiceIds.push(doc.id);
                                        });
                                    }
                                }
                            }
                        } else {
                            var refInValidServices = await database.collection('providers_services').where('author', '==', userId).get();
                            refInValidServices.forEach(doc => {
                                inValidServiceIds.push(doc.id);
                            });
                        }
                    }
                })
            }
        }
        return inValidServiceIds;
    }

    async function loadGoogleMapsScript() {
        await database.collection('settings').doc("googleMapKey").get().then(function(googleMapKeySnapshotsHeader) {
            var placeholderImageHeaderData = googleMapKeySnapshotsHeader.data();

            /* AWAITED, and it loads the maps script every address field needs.
             * An absent settings document left the key unread and threw. */
            googleMapKey = placeholderImageHeaderData ? (placeholderImageHeaderData.key || '') : '';
            const script = document.createElement('script');
            if (mapType == 'google') {
                script.src = "https://maps.googleapis.com/maps/api/js?key=" + googleMapKey + "&libraries=places";
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            } else {
                script.src = "https://unpkg.com/leaflet/dist/leaflet.js";
                document.head.appendChild(script);
            }
            script.onload = function() {
                if (mapType == 'google') {
                    initialize();
                } else {
                    init();
                }
                if (getCookie('service_type') != null || getCookie('service_type') != "" || getCookie('service_type') != undefined) {
                    if (getCookie('service_type') == "Rental Service") {
                        pickLocation();
                        dropLocation();
                    } else if (getCookie('service_type') == "Parcel Delivery Service") {
                        if (mapType == 'google') {
                            setParcelLocations();
                        } else {
                            setParcelOSM();
                        }
                    }
                }
            };
            document.head.appendChild(script);
        });
    }
    
    loadGoogleMapsScript();

    var placeholderImage = '';
    var placeholder = database.collection('settings').doc('placeHolderImage');
    placeholder.get().then(async function(snapshotsimage) {
        var placeholderImageData = snapshotsimage.data();

        if (placeholderImageData) {
            placeholderImage = placeholderImageData.image;
        }
    })
    var service_type = getCookie('service_type');
    var footerRef = database.collection('settings').doc('footerTemplate');
    footerRef.get().then(async function(snapshots) {
        var footerData = snapshots.data();
        if (footerData != undefined) {
            if (footerData.footerTemplate && footerData.footerTemplate != "" && footerData.footerTemplate != undefined) {
                $('.footerTemplate').html(footerData.footerTemplate);
            }
        }
    });
    
    function pickLocation() {
        var input = document.getElementById('pickLocation');
        if (mapType == 'google') {
            if (input) {
                var autocomplete = new google.maps.places.Autocomplete(input);
                google.maps.event.addListener(autocomplete, 'place_changed', function() {
                    var place = autocomplete.getPlace();
                    address_lat = place.geometry.location.lat();
                    address_lng = place.geometry.location.lng();
                });
            }
        } else {
            function getPlaceSuggestions(query) {
                return $.ajax({
                    url: `https://nominatim.openstreetmap.org/search?format=json&q=${query}`,
                    dataType: 'json'
                });
            }
            // Autocomplete setup for OSM
            $('#pickLocation').autocomplete({
                source: function(request, response) {
                    getPlaceSuggestions(request.term).done(function(data) {
                        response(data.map(place => ({
                            label: place.display_name,
                            lat: place.lat,
                            lon: place.lon,
                            address: place.display_name
                        })));
                    });
                },
                select: function(event, ui) {
                    address_lat = ui.item.lat;
                    address_lng = ui.item.lon;
                },
                minLength: 3
            });
        }
    }

    function dropLocation() {
        var input = document.getElementById('dropLocation');
        if (mapType == 'google') {
            if (input) {
                var autocomplete = new google.maps.places.Autocomplete(input);
                google.maps.event.addListener(autocomplete, 'place_changed', function() {
                    var place = autocomplete.getPlace();
                    drop_address_lat = place.geometry.location.lat();
                    drop_address_lng = place.geometry.location.lng();
                });
            }
        } else {
            function getPlaceSuggestions(query) {
                return $.ajax({
                    url: `https://nominatim.openstreetmap.org/search?format=json&q=${query}`,
                    dataType: 'json'
                });
            }
            // Autocomplete setup for OSM
            $('#dropLocation').autocomplete({
                source: function(request, response) {
                    getPlaceSuggestions(request.term).done(function(data) {
                        response(data.map(place => ({
                            label: place.display_name,
                            lat: place.lat,
                            lon: place.lon,
                            address: place.display_name
                        })));
                    });
                },
                select: function(event, ui) {
                    drop_address_lat = ui.item.lat;
                    drop_address_lng = ui.item.lon;
                },
                minLength: 3
            });
        }
    }

    function setParcelOSM() {
        function getPlaceSuggestions(query) {
            return $.ajax({
                url: `https://nominatim.openstreetmap.org/search?format=json&q=${query}`,
                dataType: 'json'
            });
        }
        // Autocomplete setup
        $('#senderAddress').autocomplete({
            source: function(request, response) {
                getPlaceSuggestions(request.term).done(function(data) {
                    response(data.map(place => ({
                        label: place.display_name,
                        lat: place.lat,
                        lon: place.lon,
                        address: place.address || {}
                    })));
                });
            }
        });
        $('#receiver_address').autocomplete({
            source: function(request, response) {
                getPlaceSuggestions(request.term).done(function(data) {
                    response(data.map(place => ({
                        label: place.display_name,
                        lat: place.lat,
                        lon: place.lon,
                        address: place.address || {}
                    })));
                });
            }
        });
        $('#sender_address_schedule').autocomplete({
            source: function(request, response) {
                getPlaceSuggestions(request.term).done(function(data) {
                    response(data.map(place => ({
                        label: place.display_name,
                        lat: place.lat,
                        lon: place.lon,
                        address: place.address || {}
                    })));
                });
            }
        });
        $('#receiver_address_schedule').autocomplete({
            source: function(request, response) {
                getPlaceSuggestions(request.term).done(function(data) {
                    response(data.map(place => ({
                        label: place.display_name,
                        lat: place.lat,
                        lon: place.lon,
                        address: place.address || {}
                    })));
                });
            }
        });
    }

    function setParcelLocations() {
        var input = document.getElementById('senderAddress');
        if (input) {
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    address_name = place.name || place.formatted_address || '';
                    address_lat = place.geometry.location.lat();
                    address_lng = place.geometry.location.lng();
                    $('#senderAddress').val(address_name);
                }
            });
        }
        var receiver_address = document.getElementById('receiver_address');
        if (receiver_address) {
            var autocomplete = new google.maps.places.Autocomplete(receiver_address);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    address_name = place.name || place.formatted_address || '';
                    address_lat = place.geometry.location.lat();
                    address_lng = place.geometry.location.lng();
                    $('#receiver_address').val(place.name);
                }
            });
        }
        var sender_address_schedule = document.getElementById('sender_address_schedule');
        if (sender_address_schedule) {
            var autocomplete = new google.maps.places.Autocomplete(sender_address_schedule);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    address_name = place.name || place.formatted_address || '';
                    address_lat = place.geometry.location.lat();
                    address_lng = place.geometry.location.lng();
                    $('#sender_address_schedule').val(place.name);
                }
            });
        }
        var receiver_address_schedule = document.getElementById('receiver_address_schedule');
        if (receiver_address_schedule) {
            var autocomplete = new google.maps.places.Autocomplete(receiver_address_schedule);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    address_name = place.name || place.formatted_address || '';
                    address_lat = place.geometry.location.lat();
                    address_lng = place.geometry.location.lng();
                    $('#receiver_address_schedule').val(place.name);
                }
            });
        }
    }

    /* ------------------------------------------------------------------
     * Services grouped on the section list.
     *
     * Document 1 page 9 asks for the services to be presented in groups -
     * "Online shopping & Restaurant", "Transport & Delivery", "Finance",
     * "On demand services", "Others". The admin panel has offered this since
     * 21 September: a `service_groups` collection the client manages, and a
     * `serviceGroup` on each section. THIS PANEL WAS READING NEITHER, so the
     * grouping the client set up was invisible to customers.
     *
     * The list was built twice in this file, once for the modal that opens by
     * itself and once for the one the customer opens. Both now go through
     * renderSectionList().
     *
     * THE GROUPS ARE DATA, NOT A FIXED LIST. The five on page 9 are only what
     * the admin panel seeds; the client can rename, reorder, add and unpublish
     * them, so nothing here may hardcode the five.
     * ------------------------------------------------------------------ */
    var serviceGroupsCache = null;

    async function loadServiceGroups() {
        if (serviceGroupsCache !== null) {
            return serviceGroupsCache;
        }

        serviceGroupsCache = [];

        try {
            var snapshots = await database.collection('service_groups').get();

            snapshots.docs.forEach(function (doc) {
                var group = doc.data();
                group.id = group.id || doc.id;

                /* publish false hides a group. Absent counts as published, so
                 * a group saved before the field existed still shows. */
                if (group.publish === false) {
                    return;
                }

                serviceGroupsCache.push(group);
            });

            serviceGroupsCache.sort(function (a, b) {
                return (parseInt(a.order) || 0) - (parseInt(b.order) || 0);
            });
        } catch (err) {
            /* No groups means the flat list this panel has always shown -
             * never an empty screen. */
            console.error('service groups could not be read', err);
            serviceGroupsCache = [];
        }

        return serviceGroupsCache;
    }

    /* One card. Both lists drew this separately, with one difference: the
     * modal that opens by itself wrote data-dine_in="false" for an On Demand
     * section while the other wrote whatever the section carried, usually
     * undefined. The click handler treats anything but "true" as false, so the
     * two behaved identically; this keeps the explicit one. */
    function sectionCardHtml(datas, activeSectionId) {
        var image = (datas.sectionImage != '' && datas.sectionImage != undefined)
            ? datas.sectionImage
            : placeholderImage;

        var dineIn = (datas.serviceType == "On Demand Service")
            ? 'false'
            : datas.dine_in_active;

        var active = (activeSectionId && activeSectionId == datas.id)
            ? ' section-selected'
            : '';

        return '<div class="section-list-inner col-md-3 mb-4 select_section' + active + '"' +
            ' data-color="' + datas.color + '"' +
            ' service_type="' + datas.serviceType + '"' +
            ' data-name="' + datas.name + '"' +
            ' data-dine_in="' + dineIn + '"' +
            ' data-id="' + datas.id + '">' +
            '<div class="section-block bg-white rounded d-block py-3 px-2 text-center shadow-lg">' +
            '<span class="section-img"><img alt="#" src="' + image +
            '" onerror="this.onerror=null;this.src=\'' + placeholderImage + '\'" class="img-fluid item-img w-100"></span>' +
            '<span class="section-name mt-2 d-block">' + datas.name + '</span></div></div>';
    }

    /* Group names arrive HTML-ESCAPED. The admin panel seeds them through
     * Blade's double-brace echo, which escapes, so Firestore literally holds
     * "Online Shopping &amp; Restaurant". Escaping that again would show the
     * customer the &amp; itself.
     *
     * Decoding first and then escaping handles both shapes: a name stored
     * escaped comes back to a plain ampersand, a name typed by the client
     * with a real < is still made safe before it goes near the page.
     *
     * Setting innerHTML on a detached textarea decodes entities and executes
     * nothing - a textarea has no markup of its own. */
    function escapeGroupName(value) {
        var decoder = document.createElement('textarea');
        decoder.innerHTML = String(value || '');

        return $('<div>').text(decoder.value).html();
    }

    /* Set while a load is in flight. The emptiness check in renderSectionList
     * cannot stand alone now that the modal has more than one trigger: two can
     * fire in the same tick, and the first await returns to an equally empty
     * container, so both would query Firestore and draw the list twice. */
    var sectionListLoading = false;

    /* Draws the section list into #section_lists, grouped.
     *
     * AN UNGROUPED SERVICE GOES UNDER "OTHERS", which is settled with the
     * client - page 9 lists Others as one of their own five groups. Only when
     * Others itself has been deleted or unpublished does such a service fall
     * to the top of the list with no heading, so it is never lost.
     *
     * AN EMPTY GROUP IS NOT DRAWN. A heading with nothing beneath it reads as
     * a fault.
     *
     * A section pointing at a group that has been deleted or unpublished is
     * treated as ungrouped rather than dropped - never hide a service because
     * of a setting on something else.
     */
    async function renderSectionList(activeSectionId) {
        var container = $("#section_lists");

        if (container.length === 0 || container.html() != '' || sectionListLoading) {
            return;
        }

        sectionListLoading = true;

        try {
            await drawSectionList(container, activeSectionId);
        } finally {
            sectionListLoading = false;
        }
    }

    async function drawSectionList(container, activeSectionId) {

        var snapshots = await database.collection('sections')
            .where('isActive', '==', true).orderBy('order').get();

        var groups = await loadServiceGroups();
        var known = {};
        groups.forEach(function (group) { known[String(group.id)] = []; });

        /* Where an unplaced service goes. "others" is the id the admin panel
         * seeds; the client may rename the group freely, which does not change
         * the id, but they may also delete or unpublish it - hence the null. */
        var othersId = known['others'] !== undefined ? 'others' : null;

        var ungrouped = [];

        snapshots.docs.forEach(function (doc) {
            var datas = doc.data();

            /* Region first, grouping second - a service not offered where the
             * customer is must not appear under any heading. */
            if (!sectionMatchesRegion(datas)) {
                return;
            }

            var groupId = String(datas.serviceGroup || '');

            /* A service with no group, or one pointing at a group that has
             * been deleted or unpublished, goes under OTHERS.
             *
             * Settled with the client, recorded in the admin repo's
             * MESSAGES-AND-QUESTIONS-LOG.txt: page 9 lists Others as one of
             * their own five groups, holding the AI Assistant. It is where an
             * unplaced service belongs.
             *
             * If Others itself is gone - the client can unpublish or delete
             * it - such a service is listed first with no heading rather than
             * hidden. Never lose a service to a setting on something else. */
            if (groupId !== '' && known[groupId]) {
                known[groupId].push(datas);
            } else if (othersId !== null) {
                known[othersId].push(datas);
            } else {
                ungrouped.push(datas);
            }
        });

        var html = '';

        ungrouped.forEach(function (datas) {
            html += sectionCardHtml(datas, activeSectionId);
        });

        groups.forEach(function (group) {
            var members = known[String(group.id)];

            if (!members || members.length === 0) {
                return;
            }

            html += '<div class="col-12 section-group-heading mb-2">' +
                '<h6 class="font-weight-bold mb-0">' +
                escapeGroupName(group.name) +
                '</h6></div>';

            members.forEach(function (datas) {
                html += sectionCardHtml(datas, activeSectionId);
            });
        });

        container.append(html);
    }

    if (typeof is_layer != "undefined") {
        $(".select-sec-btn").hide();
    }
    
    if (address_name == "" || address_name == null) {
        <?php if (Request::path() !== 'terms' && Request::path() !== 'privacy' && Request::path() !== 'contact-us' && Request::path() !== 'faq') { ?>
        if (typeof is_layer == "undefined") {
            $('#locationModal').trigger('click');
            $('.locationModalTitle').html('{{ trans('lang.find_vendors_items_near_you') }}');
        }
        <?php } ?>
    } else {
        if (getCookie('section_id') == null || getCookie('section_id') == "" || getCookie('section_id') == undefined) {
            <?php if (Request::path() !== 'terms' && Request::path() !== 'privacy' && Request::path() !== 'contact-us' && Request::path() !== 'faq') { ?>
            if (typeof is_layer == "undefined") {
                $('#select_store_model_call')[0].click();
            }
            <?php } ?>
            renderSectionList('');
        }
    }

    if (cuser_id != "") {
        var userDetailsRef = database.collection('users').where('id', "==", cuser_id);
    }
    
    /* THE SECTION LIST LOADS WHEN THE MODAL OPENS, not when one particular
     * button is clicked.
     *
     * The footer's own "Select Section" tab used to be the only trigger that
     * filled it, so every other way into the same modal opened it EMPTY - the
     * "choose another service" button on a coming-soon page did exactly that,
     * and anything added later would have done the same.
     *
     * BOUND BOTH WAYS ON PURPOSE. This theme loads Bootstrap 4 and Bootstrap
     * 5. BS4 fires show.bs.modal through jQuery, which a native listener never
     * sees; BS5 dispatches a real DOM event, which a jQuery handler never
     * sees. Whichever one ends up handling the modal, one of these fires.
     * renderSectionList returns early once the list is filled, so being called
     * twice costs nothing. */
    function loadSectionListForModal() {
        renderSectionList("<?php echo @$_COOKIE['section_id']; ?>");
    }

    $('#select_store_model').on('show.bs.modal', loadSectionListForModal);

    var selectStoreModelEl = document.getElementById('select_store_model');

    if (selectStoreModelEl) {
        selectStoreModelEl.addEventListener('show.bs.modal', loadSectionListForModal);
    }

    /* Kept as well: this tab is clicked programmatically above when a visitor
     * arrives with no section, and the click is the one trigger that does not
     * depend on either Bootstrap handling the modal. */
    $('#select_store_model_call').bind('click', loadSectionListForModal);
    
  
    function init() {
      
        var inputIds = ['user_locationnew', 'user_locationnew_mobile'];
        inputIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && typeof address_name !== 'undefined' && address_name != '') {
                el.value = address_name;
            }
        });

        function getPlaceSuggestions(query) {
            return $.ajax({
                /* addressdetails=1 is what makes Nominatim return the `address`
                 * object. Without it `place.address` is undefined and every
                 * component below falls to ''. */
                url: `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=${query}`,
                dataType: 'json'
            });
        }

        // Autocomplete setup
        $('#user_locationnew, #user_locationnew_mobile').autocomplete({
            source: function(request, response) {
                getPlaceSuggestions(request.term).done(function(data) {
                    response(data.map(place => ({
                        label: place.display_name,
                        lat: place.lat,
                        lon: place.lon,
                        address: place.address || {}
                    })));
                });
            },
            select: async function(event, ui) {
                var address_name = ui.item.label;
                var address_lat = ui.item.lat;
                var address_lng = ui.item.lon;
                var address = ui.item.address || {}; // Default to empty object if address is undefined
                // Extract address components from the selected place
                var address_name1 = ui.item.address.road || '';
                var address_name2 = ui.item.address.neighbourhood || ui.item.address.suburb || '';
                var address_zip = ui.item.address.postcode || '';
                var address_city = ui.item.address.city || ui.item.address.town || ui.item.address
                    .village || '';
                var address_state = ui.item.address.state || '';
                var address_country = ui.item.address.country || '';
                // Set the cookies for the selected address details
                setCookie('address_name1', address_name1, 365);
                setCookie('address_name2', address_name2, 365);
                setCookie('address_name', address_name, 365);
                setCookie('address_lat', address_lat, 365);
                setCookie('address_lng', address_lng, 365);
                setCookie('address_zip', address_zip, 365);
                setCookie('address_city', address_city, 365);
                setCookie('address_state', address_state, 365);
                setCookie('address_country', address_country, 365);
                await setUserCountryCookie(address_lat, address_lng);
                /* Reload last: it used to run first, which raced every write
                 * above it. */
                window.location.reload(true);
            }
        });
    }
    
    function initialize() {

        var inputIds = ['user_locationnew', 'user_locationnew_mobile'];
        inputIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el && typeof address_name !== 'undefined' && address_name != '') {
                el.value = address_name;
            }
        });

         // Desktop autocomplete
        var desktopInput = document.getElementById('user_locationnew');
        if (desktopInput) {
            var desktopAutocomplete = new google.maps.places.Autocomplete(desktopInput);
            google.maps.event.addListener(desktopAutocomplete, 'place_changed', function() {
                var place = desktopAutocomplete.getPlace();
                if (!place.geometry) return;
                setAddressCookies(place);
            });
        }

        // Mobile autocomplete
        var mobileInput = document.getElementById('user_locationnew_mobile');
        if (mobileInput) {
            var mobileAutocomplete = new google.maps.places.Autocomplete(mobileInput);
            google.maps.event.addListener(mobileAutocomplete, 'place_changed', function() {
                var place = mobileAutocomplete.getPlace();
                if (!place.geometry) return;
                setAddressCookies(place);
            });
        }
    }
    function setLocationValue(prop, val) {
        ['user_locationnew', 'user_locationnew_mobile'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el[prop] = val;
        });
    }

    /* ---- Delivery-address modal -----------------------------------------
     * The "find vendors and items near you" modal at the top of this file.
     * There are two ways to fill it: the map-pin button geolocates, and
     * typing in Delivery Area offers place suggestions.
     *
     * Both routes end at fillAddressModal(), so they cannot drift apart -
     * which is how the country field came to be populated on one path and
     * silently skipped on another.
     * ------------------------------------------------------------------- */

    function addressModalStatus(message, isError) {
        var el = $('#address_modal_status');
        if (!el.length) {
            return;
        }
        if (!message) {
            el.hide().text('');
            return;
        }
        el.text(message)
            .toggleClass('text-danger', !!isError)
            .toggleClass('text-muted', !isError)
            .show();
    }

    function fillAddressModal(parts) {
        if (!parts) {
            return;
        }
        if (parts.line1 !== undefined) $('#address_line1').val(parts.line1);
        if (parts.line2 !== undefined) $('#address_line2').val(parts.line2);
        if (parts.zip !== undefined) $('#address_zipcode').val(parts.zip);
        if (parts.city !== undefined) $('#address_city').val(parts.city);
        if (parts.country !== undefined) $('#address_country').val(parts.country);

        if (parts.lat !== undefined && parts.lat !== '' && parts.lat !== null) {
            $('#address_lat').val(parts.lat);
            address_lat = parts.lat;
        }
        if (parts.lng !== undefined && parts.lng !== '' && parts.lng !== null) {
            $('#address_lng').val(parts.lng);
            address_lng = parts.lng;
        }

        /* Keep the page-level name and the header's location box showing the
         * same place the modal now holds. */
        if (parts.line1) {
            address_name = parts.line1;
            var header = document.getElementById('user_locationnew');
            if (header) {
                header.value = parts.line1;
            }
        }
    }

    /* Google tags a component with several types at once, so this looks for
     * membership rather than reading types[0] - a city tagged
     * ["locality","political"] is found either way, but one tagged
     * ["political","locality"] is missed by the first-type test. */
    function googleComponent(components, type) {
        for (var i = 0; i < components.length; i++) {
            if ((components[i].types || []).indexOf(type) !== -1) {
                return components[i].long_name;
            }
        }
        return '';
    }

    function addressPartsFromGoogle(place) {
        var components = place.address_components || [];
        var streetNumber = googleComponent(components, 'street_number');
        var route = googleComponent(components, 'route');

        var parts = {
            line1: place.formatted_address
                || [streetNumber, route].filter(Boolean).join(' ')
                || place.name
                || '',
            line2: googleComponent(components, 'premise')
                || googleComponent(components, 'neighborhood')
                || googleComponent(components, 'sublocality_level_1')
                || googleComponent(components, 'sublocality')
                || '',
            zip: googleComponent(components, 'postal_code'),
            /* Not every address has a locality: rural and non-US addresses
             * often carry only a postal town or a district. */
            city: googleComponent(components, 'locality')
                || googleComponent(components, 'postal_town')
                || googleComponent(components, 'administrative_area_level_2')
                || googleComponent(components, 'administrative_area_level_1'),
            country: googleComponent(components, 'country')
        };

        if (place.geometry && place.geometry.location) {
            parts.lat = place.geometry.location.lat();
            parts.lng = place.geometry.location.lng();
        }
        return parts;
    }

    /* Writes the header location box's address cookies from a Google place.
 *
 * The stock panel CALLS this from initialize() - on both the desktop and
 * mobile header autocompletes - but never defines it anywhere, so picking a
 * suggestion threw "setAddressCookies is not defined" and nothing was saved.
 * That is the google branch, which is the one that runs whenever
 * settings/DriverNearBy.selectedMapType is 'google'; the OSM branch below
 * does the same job and was always complete.
 *
 * Mirrors that OSM handler exactly: write every address cookie, keep the
 * country cookies in step, then reload so the rest of the page picks the
 * new location up. */
    async function setAddressCookies(place) {
        if (!place) {
            return;
        }

        var parts = addressPartsFromGoogle(place);
        var components = place.address_components || [];

        setCookie('address_name', parts.line1 || '', 365);
        setCookie('address_name1', googleComponent(components, 'route') || parts.line2 || '', 365);
        setCookie('address_name2', parts.line2 || '', 365);
        setCookie('address_zip', parts.zip || '', 365);
        setCookie('address_city', parts.city || '', 365);
        setCookie('address_state', googleComponent(components, 'administrative_area_level_1'), 365);
        setCookie('address_country', parts.country || '', 365);

        if (parts.lat !== undefined && parts.lng !== undefined) {
            setCookie('address_lat', parts.lat, 365);
            setCookie('address_lng', parts.lng, 365);
            address_lat = parts.lat;
            address_lng = parts.lng;
            /* Drives the tax lookup and region detection. */
            await setUserCountryCookie(parts.lat, parts.lng);
        }

        window.location.reload(true);
    }

    /* Shapes a Nominatim result, from either /search or /reverse - both carry
     * the same `address` object once addressdetails=1 is asked for. */
    function addressPartsFromOsm(data) {
        var address = (data && data.address) || {};
        return {
            line1: (data && data.display_name) || '',
            line2: address.neighbourhood || address.suburb || address.road || '',
            zip: address.postcode || '',
            city: address.city || address.town || address.village || address.county || '',
            country: address.country || '',
            lat: data ? data.lat : '',
            lng: data ? data.lon : ''
        };
    }

    function reverseGeocodeGoogle(lat, lng) {
        return new Promise(function (resolve) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ 'location': { lat: lat, lng: lng } }, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK && results && results.length) {
                    resolve(addressPartsFromGoogle(results[0]));
                } else {
                    resolve(null);
                }
            });
        });
    }

    async function reverseGeocodeOsm(lat, lng) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=' + lat + '&lon=' + lng;
        var response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        var data = await response.json();
        return (data && data.address) ? addressPartsFromOsm(data) : null;
    }

    /* The map-pin button. It used to assume Google was loaded and said
     * nothing at all when the browser refused the location, which is
     * indistinguishable from a dead button. */
    async function getCurrentLocationAddress1() {
        if (!navigator.geolocation) {
            addressModalStatus("{{ trans('lang.location_unavailable') }}", true);
            return;
        }

        $('#use_my_location').prop('disabled', true);
        addressModalStatus("{{ trans('lang.locating_you') }}", false);

        navigator.geolocation.getCurrentPosition(async function (position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            try {
                var useGoogle = (mapType === 'google' && typeof google !== 'undefined' && google.maps);
                var parts = useGoogle
                    ? await reverseGeocodeGoogle(lat, lng)
                    : await reverseGeocodeOsm(lat, lng);

                if (!parts) {
                    addressModalStatus("{{ trans('lang.address_lookup_failed') }}", true);
                    return;
                }

                parts.lat = lat;
                parts.lng = lng;
                fillAddressModal(parts);
                addressModalStatus('', false);

                /* userCountryName/userCountryCode drive the tax lookup and
                 * region detection, so they follow the pin too. */
                await setUserCountryCookie(lat, lng);
            } catch (err) {
                addressModalStatus("{{ trans('lang.address_lookup_failed') }}", true);
            } finally {
                $('#use_my_location').prop('disabled', false);
            }
        }, function (error) {
            $('#use_my_location').prop('disabled', false);
            addressModalStatus(
                (error && error.code === error.PERMISSION_DENIED)
                    ? "{{ trans('lang.location_permission_denied') }}"
                    : "{{ trans('lang.location_unavailable') }}",
                true
            );
        }, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        });
    }

    /* Suggestions while typing in Delivery Area.
     *
     * This used to be set up only on the checkout route, four seconds after
     * load - so the modal that opens on landing, which is the one most
     * customers meet first, had no suggestions at all. It is now bound
     * wherever the modal is, when the modal opens. */
    var addressModalAutocompleteBound = false;

    function initAddressModalAutocomplete(attempt) {
        if (addressModalAutocompleteBound) {
            return;
        }
        var input = document.getElementById('address_line1');
        if (!input) {
            return;
        }

        /* Google's Autocomplete does not predict when it is attached to a
         * hidden input, and binding it while the modal is closed also sets the
         * flag below, so the shown.bs.modal call that would have bound it
         * properly returns early and the field silently offers nothing.
         *
         * This only showed up once the location guard meant the modal usually
         * opens on demand rather than automatically at page load. */
        if (input.offsetParent === null) {
            return;
        }

        if (mapType === 'google') {
            /* The Maps script is only fetched after settings/googleMapKey has
             * been read, so it is often not there yet when the modal opens on
             * landing. Wait for it rather than binding nothing. */
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
                if ((attempt || 0) < 30) {
                    setTimeout(function () {
                        initAddressModalAutocomplete((attempt || 0) + 1);
                    }, 500);
                }
                return;
            }

            var autocomplete = new google.maps.places.Autocomplete(input);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                var place = autocomplete.getPlace();
                if (!place || !place.geometry) {
                    return;
                }
                fillAddressModal(addressPartsFromGoogle(place));
                addressModalStatus('', false);
            });
            addressModalAutocompleteBound = true;
            return;
        }

        $(input).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&q=' + encodeURIComponent(request.term),
                    dataType: 'json'
                }).done(function (data) {
                    response((data || []).map(function (place) {
                        return {
                            label: place.display_name,
                            value: place.display_name,
                            place: place
                        };
                    }));
                }).fail(function () {
                    response([]);
                });
            },
            select: function (event, ui) {
                fillAddressModal(addressPartsFromOsm(ui.item.place));
                addressModalStatus('', false);
            },
            minLength: 3
        }).autocomplete('widget').css('z-index', 99999);

        addressModalAutocompleteBound = true;
    }

    $(document).on('shown.bs.modal', '#locationModalAddress', function () {
        addressModalStatus('', false);
        initAddressModalAutocomplete();
    });

    $(function () {
        initAddressModalAutocomplete();
    });

    var currentCurrency = "";
    var currencyAtRight = false;
    var decimal_degits = 0;
    var currencyData = '';
    /* The fetch that fills these lives at the end of the LAST script block in
     * this file, because it calls regionCurrencyRef(), which is declared
     * there. Function declarations hoist within a script block, not across
     * them - this file has four. */

    let taxBreakdownGrouped = {
        item: {},
        order: {},
        delivery: {},
        packaging: {},
        platform: {}
    };
    
    async function sendMailData(orderId, userId) {
        
        const emailTemplatesPromise = database.collection('email_templates').where('type', '==', 'new_order_placed').limit(1).get();
        const [orderRef, userRef, emailTempSnapshot] = await Promise.all([
            database.collection('vendor_orders').doc(orderId).get(),
            database.collection('users').doc(userId).get(),
            emailTemplatesPromise
        ]);
        /* NO TEMPLATE MEANS NO EMAIL. The admin can rename or delete a
         * template type, and docs[0] on an empty result threw - DURING
         * ORDER PLACEMENT, so a missing template failed the order rather
         * than just the mail. */
        if (emailTempSnapshot.empty) return;

        if (!orderRef.exists || !userRef.exists) return;

        const orderDetails = orderRef.data();
        const userDetails = userRef.data();
        const emailTemplatesData = emailTempSnapshot.docs[0].data();
        
        let orderUserName = userDetails.firstName+' '+userDetails.lastName;
        let orderUserEmail = userDetails.email;
        
        var order_subtotal = 0;
        var total_discount = 0;
        var total_tax_amount = 0;
        var tip_amount = parseFloat(orderDetails.tip_amount || 0);
        var deliveryCharge = parseFloat(orderDetails.deliveryCharge || 0);
        var platformFee = parseFloat(orderDetails.platformFee || 0);
        var packagingCharge = orderDetails.packagingChargeEnable ? parseFloat(orderDetails.vendor.packagingCharge || 0) : 0;

        // Calculate subtotal and product extras
        for (let i = 0; i < orderDetails.products.length; i++) {
            let product = orderDetails.products[i];
            let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
            let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
            order_subtotal += itemGross;
        }

         // Total discounts
        let order_discount = parseFloat(orderDetails.discount || 0);
        let special_discount = parseFloat(orderDetails.specialDiscount?.special_discount || 0);
            total_discount = order_discount + special_discount;

        // Calculate item-level taxes (if product-level)
        if (orderDetails.taxScope === "product") {
            let itemSubtotal = order_subtotal;
            let itemCombinedTax = 0;
            orderDetails.products.forEach(product => {
                let basePrice = (product.discountPrice && parseFloat(product.discountPrice) > 0) ? parseFloat(product.discountPrice) : parseFloat(product.price);
                let itemGross = (basePrice + parseFloat(product.extras_price || 0)) * parseInt(product.quantity);
                let itemDiscount = (itemSubtotal > 0) ? (itemGross / itemSubtotal) * total_discount : 0;
                let itemTaxable = Math.max(0, itemGross - itemDiscount);
                let itemTaxes = product.taxSetting || [];
                itemTaxes.forEach(tax => {
                    if (tax.enable) {
                        let taxAmount = 0;
                        if (tax.type === "percentage") {
                            taxAmount = (tax.tax / 100) * itemTaxable;
                        } else {
                            taxAmount = tax.tax;
                        }
                        total_tax_amount += parseFloat(taxAmount);
                        itemCombinedTax += parseFloat(taxAmount);
                    }
                });
            });
            if(itemCombinedTax > 0){
                taxBreakdownGrouped.item[''] = itemCombinedTax;
            }
        } 

        // Order-level taxes (if order-level)
        if (orderDetails.taxScope === "order") {
            let orderTaxable = Math.max(0, order_subtotal - total_discount);
            let orderCombinedTax = 0;
            (orderDetails.taxSetting || []).forEach(tax => {
                if (tax.enable) {
                    let taxAmount = 0;
                    if (tax.type === "percentage") {
                        taxAmount = (tax.tax / 100) * orderTaxable;
                    } else {
                        taxAmount = tax.tax;
                    }
                    total_tax_amount += parseFloat(taxAmount);
                    orderCombinedTax += parseFloat(taxAmount);
                }
            });
            if(orderCombinedTax > 0){
                taxBreakdownGrouped.order[''] = orderCombinedTax;
            }
        }

        // Delivery, packaging, platform taxes
        let extraCharges = [
            {key: 'delivery', amount: deliveryCharge, taxes: orderDetails.driverDeliveryTax || []},
            {key: 'packaging', amount: packagingCharge, taxes: orderDetails.packagingTax || []},
            {key: 'platform', amount: platformFee, taxes: orderDetails.platformTax || []},
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
                    taxBreakdownGrouped[scope.key][tax.title] = (taxBreakdownGrouped[scope.key][tax.title] || 0) + parseFloat(taxAmount);
                }
            });
        });
        
        // Final total
        var order_total = (order_subtotal - total_discount) + deliveryCharge + tip_amount + (packagingChargeEnable ? packagingCharge : 0) + platformFee + total_tax_amount;

        var subTotalText = formatCurrency(order_subtotal, currencyData);
        var discountText = formatCurrency(order_discount, currencyData);
        var deliveryChargeText = formatCurrency(deliveryCharge, currencyData);
        var packagingChargeText = formatCurrency(packagingCharge, currencyData);
        var platformFeeText = formatCurrency(platformFee, currencyData);
        var tipAmountText = formatCurrency(tip_amount, currencyData);
        var totalAmountText = formatCurrency(order_total, currencyData);
        
        var productDetailsHtml = '';
        orderDetails.products.forEach((product) => {
            productDetailsHtml += '<tr>';
            var extra_html = '';
            var extras_price = 0;
            var basePriceValue = (product.discountPrice !== undefined &&
                      product.discountPrice !== null &&
                      parseFloat(product.discountPrice) > 0)
                    ? parseFloat(product.discountPrice)
                    : parseFloat(product.price);

            var price_item = basePriceValue.toFixed(decimal_degits);
            var totalProductPrice = parseFloat(price_item) * parseInt(product.quantity);
            
            if (product.extras != undefined && product.extras != '' && product.extras.length > 0) {
                var extra_count = 0;
                let extras_price_item = (parseFloat(product.extras_price || 0) * parseInt(product.quantity));
                if (!isNaN(extras_price_item)) {
                    extras_price = extras_price_item.toFixed(decimal_degits);
                    totalProductPrice += parseFloat(extras_price);
                }
                product.extras.forEach((extra) => {
                    if (extra_count > 1) {
                        extra_html = extra_html + ',' + extra;
                    } else {
                        extra_html = extra_html + extra;
                    }
                    extra_count++;
                })
            }
            productDetailsHtml += '<td style="width: 20%; border-top: 1px solid rgb(0, 0, 0);">';
            productDetailsHtml += product.name;
            if (extra_count > 0) {
                productDetailsHtml += '<br> {{ trans('lang.extra_item') }} : ' + extra_html;
            }
            
            var price_item = formatCurrency(price_item, currencyData);
            var extras_price = formatCurrency(extras_price, currencyData);
            var totalProductPrice = formatCurrency(totalProductPrice, currencyData);

            productDetailsHtml += '</td>';
            productDetailsHtml += '<td style="width: 20%; border: 1px solid rgb(0, 0, 0);">' + product
                .quantity + '</td><td style="width: 20%; border: 1px solid rgb(0, 0, 0);">' + price_item +
                '</td><td style="width: 20%; border: 1px solid rgb(0, 0, 0);">' + extras_price +
                '</td><td style="width: 20%; border: 1px solid rgb(0, 0, 0);">  ' + totalProductPrice +
                '</td>';
            productDetailsHtml += '</tr>';
        });

        var productHtml =
        '<table style="width: 100%; border-collapse: collapse; border: 1px solid rgb(0, 0, 0);">\n' +
        '    <thead>\n' +
        '        <tr>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.product_name') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.quantity_plural') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.price') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.extra_item') }} {{ trans('lang.price') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.total') }}<br></th>\n' +
        '        </tr>\n' +
        '    </thead>\n' +
        '    <tbody id="productDetails">' + productDetailsHtml + '</tbody>\n' +
        '</table>';

        var specialDiscountVal = '';
        var specialDiscountAmount = 0;
        var totalAmount = 0;
        if (orderDetails.specialDiscount.specialType != '') {
            specialDiscountAmount = parseFloat(orderDetails.specialDiscount.special_discount).toFixed(2);
            if (orderDetails.specialDiscount.specialType == "percentage") {
                specialDiscountVal = orderDetails.specialDiscount.special_discount_label + '%';
            } else {
                specialDiscountVal = formatCurrency(orderDetails.specialDiscount.special_discount_label, currencyData);
            }
        }
        var specialDiscountAmountText = formatCurrency(specialDiscountAmount, currencyData);

        /* 02#18: hasOwnProperty is TRUE when the field holds null, which is
         * how "null" reached the screen. The helper drops absent parts, strips
         * "null" out of an already-joined locality, and returns '' when there
         * is no address at all - so the old guard is no longer needed. */
        var shippingddress = spideliFormatAddress(orderDetails.address);

        let formattedDate = new Date().toLocaleDateString('en-GB');

        var subject = emailTemplatesData.subject;
        subject = subject.replace(/{orderid}/g, orderDetails.id);

        emailTemplatesData.subject = subject;
        var message = emailTemplatesData.message;

        message = message.replace(/{username}/g, orderUserName);
        message = message.replace(/{orderid}/g, orderDetails.id);
        message = message.replace(/{date}/g, formattedDate);
        message = message.replace(/{address}/g, shippingddress);
        message = message.replace(/{paymentmethod}/g, orderDetails.payment_method);
        message = message.replace(/{productdetails}/g, productHtml);
        message = message.replace(/{subtotal}/g, subTotalText);

        if (orderDetails.couponCode) {
            message = message.replace(/{coupon}/g, '(' + orderDetails.couponCode + ')');
        } else {
            message = message.replace(/{coupon}/g, "");
        }
        message = message.replace(/{discountamount}/g, discountText);
        
        if (specialDiscountVal != '') {
            message = message.replace(/{specialcoupon}/g, '(' + specialDiscountVal + ')');
        } else {
            message = message.replace(/{specialcoupon}/g, "");
        }

        message = message.replace(/{specialdiscountamount}/g, specialDiscountAmountText);
        message = message.replace(/{shippingcharge}/g, deliveryChargeText);
        message = message.replace(/{packagingcharge}/g, packagingChargeText);
        message = message.replace(/{platformcharge}/g, platformFeeText);
        message = message.replace(/{tipamount}/g, tipAmountText);

        var taxDetailsHtml = renderMailTaxSection('item', 'Tax on Item Total');
        taxDetailsHtml += renderMailTaxSection('order', 'Tax on Order Total');
        taxDetailsHtml += renderMailTaxSection('delivery', 'Tax on Delivery Fee');
        taxDetailsHtml += renderMailTaxSection('packaging', 'Tax on Packaging Fee');
        taxDetailsHtml += renderMailTaxSection('platform', 'Tax on Platform Fee');
        taxDetailsHtml += `<strong>Total Tax : ${formatCurrency(total_tax_amount, currencyData)}</strong><br>`;
        if (taxDetailsHtml != '') {
            message = message.replace(/{taxdetails}/g, taxDetailsHtml);
        } else {
            message = message.replace(/{taxdetails}/g, "");
        }
        message = message.replace(/{totalAmount}/g, totalAmountText);
        
        emailTemplatesData.message = message;

        var url = "{{ url('send-email') }}";
        return await sendEmail(url, emailTemplatesData.subject, emailTemplatesData.message, [orderUserEmail]);
    }

    async function sendOnDemandMailData(orderId, serviceId, userId) {
        
        const emailTemplatesPromise = database.collection('email_templates').where('type', '==', 'new_ondemand_book').limit(1).get();
        const [orderRef, userRef, serviceRef, emailTempSnapshot] = await Promise.all([
            database.collection('provider_orders').doc(orderId).get(),
            database.collection('users').doc(userId).get(),
            database.collection('providers_services').doc(serviceId).get(),
            emailTemplatesPromise
        ]);
        /* NO TEMPLATE MEANS NO EMAIL. The admin can rename or delete a
         * template type, and docs[0] on an empty result threw - DURING
         * ORDER PLACEMENT, so a missing template failed the order rather
         * than just the mail. */
        if (emailTempSnapshot.empty) return;

        if (!orderRef.exists || !userRef.exists || !serviceRef.exists) return;

        const orderDetails = orderRef.data();
        const userDetails = userRef.data();
        const serviceDetails = serviceRef.data();
        const emailTemplatesData = emailTempSnapshot.docs[0].data();

        let orderUserName = userDetails.firstName+' '+userDetails.lastName;
        let orderUserEmail = userDetails.email;
        
        var order_subtotal = 0;
        var total_discount = parseFloat(orderDetails.discount || 0);
        var total_tax_amount = 0;
        var platformFee = parseFloat(orderDetails.platformFee || 0);
        
        //  Calculate subtotal and product extras
        let basePrice = (serviceDetails.disPrice && parseFloat(serviceDetails.disPrice) > 0) ? parseFloat(serviceDetails.disPrice) : parseFloat(serviceDetails.price);
        let itemGross = basePrice * parseFloat(orderDetails.quantity);
        order_subtotal = itemGross;
        
        // Order-level taxes (if order-level)
        let orderTaxable = Math.max(0, order_subtotal - total_discount);
        let orderCombinedTax = 0;
        (orderDetails.taxSetting || []).forEach(tax => {
            if (tax.enable) {
                let taxAmount = 0;
                if (tax.type === "percentage") {
                    taxAmount = (tax.tax / 100) * orderTaxable;
                } else {
                    taxAmount = tax.tax;
                }
                total_tax_amount += parseFloat(taxAmount);
                orderCombinedTax += parseFloat(taxAmount);
            }
        });
        if(orderCombinedTax > 0){
            taxBreakdownGrouped.order[''] = orderCombinedTax;
        }

        // Delivery, packaging, platform taxes
        let extraCharges = [
            {key: 'platform', amount: platformFee, taxes: orderDetails.platformTax || []},
        ];

        extraCharges.forEach(scope => {
            scope.taxes?.forEach(tax => {
                if (tax.enable) {
                    let taxAmount = 0;
                    if (tax.type === "percentage") {
                        taxAmount = (tax.tax / 100) * scope.amount;
                    } else {
                        taxAmount = tax.tax;
                    }
                    total_tax_amount += parseFloat(taxAmount);
                    taxBreakdownGrouped[scope.key][tax.title] = (taxBreakdownGrouped[scope.key][tax.title] || 0) + parseFloat(taxAmount);
                }
            });
        });
        
        var subTotalText = formatCurrency(order_subtotal, currencyData);

        //Final subtotal after discounts
        order_subtotal = order_subtotal - total_discount;
        
        // Final total
        var order_total = order_subtotal + platformFee + total_tax_amount;

        var extraChargesHtml = '';
        if (orderDetails.extraCharges != "" && orderDetails.extraCharges != null) {
            extraChargesHtml += `<strong>{{ trans('lang.extra_charges') }} : ${formatCurrency(parseFloat(orderDetails.extraCharges), currencyData)}</strong><br>`;
        }

        var discountText = formatCurrency(total_discount, currencyData);
        var platformFeeText = formatCurrency(platformFee, currencyData);
        var totalAmountText = formatCurrency(order_total, currencyData);
        
        var priceUnit = (serviceDetails.priceUnit == 'Hourly') ? ' /Hour' : '';
        var price_item = formatCurrency(basePrice, currencyData);
        var totalProductPrice = formatCurrency(itemGross, currencyData);
        
        var productDetailsHtml = '';
        productDetailsHtml += '<tr>';
        productDetailsHtml += '<td style="width: 20%; border-top: 1px solid rgb(0, 0, 0);">';
                productDetailsHtml += serviceDetails.title;
        productDetailsHtml += '</td>';
        productDetailsHtml += '<td style="width: 20%; border: 1px solid rgb(0, 0, 0);">' + orderDetails.quantity + '</td><td style="width: 20%; border: 1px solid rgb(0, 0, 0);">' + price_item + priceUnit + '<td style="width: 20%; border: 1px solid rgb(0, 0, 0);">  ' + totalProductPrice + '</td>';
        productDetailsHtml += '</tr>';

        var productHtml = '<table style="width: 100%; border-collapse: collapse; border: 1px solid rgb(0, 0, 0);">\n' +
        '    <thead>\n' +
        '        <tr>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.service') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.quantity_plural') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.price') }}<br></th>\n' +
        '            <th style="text-align: left; border: 1px solid rgb(0, 0, 0);">{{ trans('lang.total') }}<br></th>\n' +
        '        </tr>\n' +
        '    </thead>\n' +
        '    <tbody id="productDetails">' + productDetailsHtml + '</tbody>\n' +
        '</table>';

        /* 02#18: hasOwnProperty is TRUE when the field holds null, which is
         * how "null" reached the screen. The helper drops absent parts, strips
         * "null" out of an already-joined locality, and returns '' when there
         * is no address at all - so the old guard is no longer needed. */
        var shippingddress = spideliFormatAddress(orderDetails.address);

        let formattedDate = new Date().toLocaleDateString('en-GB');

        var subject = emailTemplatesData.subject;
        subject = subject.replace(/{orderid}/g, orderDetails.id);

        emailTemplatesData.subject = subject;
        var message = emailTemplatesData.message;

        message = message.replace(/{username}/g, orderUserName);
        message = message.replace(/{orderid}/g, orderDetails.id);
        message = message.replace(/{date}/g, formattedDate);
        message = message.replace(/{address}/g, shippingddress);
        message = message.replace(/{paymentmethod}/g, orderDetails.payment_method);
        message = message.replace(/{productdetails}/g, productHtml);
        message = message.replace(/{subtotal}/g, subTotalText);

        if (orderDetails.couponCode) {
            message = message.replace(/{coupon}/g, '(' + orderDetails.couponCode + ')');
        } else {
            message = message.replace(/{coupon}/g, "");
        }
        message = message.replace(/{discountamount}/g, discountText);
        message = message.replace(/{platformcharge}/g, platformFeeText);
        
        var taxDetailsHtml = renderMailTaxSection('order', 'Tax on Order Total');
        taxDetailsHtml += renderMailTaxSection('platform', 'Tax on Platform Fee');
        taxDetailsHtml += `<strong>Total Tax : ${formatCurrency(total_tax_amount, currencyData)}</strong><br>`;
        if (taxDetailsHtml != '') {
            message = message.replace(/{taxdetails}/g, taxDetailsHtml);
        } else {
            message = message.replace(/{taxdetails}/g, "");
        }
        message = message.replace(/{totalAmount}/g, totalAmountText);
        message = message.replace(/{extracharges}/g, extraChargesHtml);
        
        emailTemplatesData.message = message;

        var url = "{{ url('send-email') }}";
        return await sendEmail(url, emailTemplatesData.subject, emailTemplatesData.message, [orderUserEmail]);
    }

    async function sendMailToParcel(orderId, userId) {

        const emailTemplatesPromise = database.collection('email_templates').where('type', '==', 'new_parcel_book').limit(1).get();
        const [orderRef, userRef, emailTempSnapshot] = await Promise.all([
            database.collection('parcel_orders').doc(orderId).get(),
            database.collection('users').doc(userId).get(),
            emailTemplatesPromise
        ]);
        /* NO TEMPLATE MEANS NO EMAIL. The admin can rename or delete a
         * template type, and docs[0] on an empty result threw - DURING
         * ORDER PLACEMENT, so a missing template failed the order rather
         * than just the mail. */
        if (emailTempSnapshot.empty) return;

        if (!orderRef.exists || !userRef.exists) return;

        const orderDetails = orderRef.data();
        const userDetails = userRef.data();
        const emailTemplatesData = emailTempSnapshot.docs[0].data();
        
        let userName = userDetails.firstName+' '+userDetails.lastName;
        let userEmail = userDetails.email;
        let userPhone = userDetails.phoneNumber;

        let senderName = orderDetails?.sender?.name || userName;
        let senderphone = orderDetails?.sender?.phone || userPhone;
        let note = orderDetails?.note || '';

        let dateObj = orderDetails.senderPickupDateTime?.toDate ? orderDetails.senderPickupDateTime.toDate() : new Date(orderDetails.senderPickupDateTime);
        let day = String(dateObj.getDate()).padStart(2, '0');
        let month = String(dateObj.getMonth() + 1).padStart(2, '0');
        let year = dateObj.getFullYear();
        
        let hours = dateObj.getHours();
        let minutes = String(dateObj.getMinutes()).padStart(2, '0');
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // 0 becomes 12
        hours = String(hours).padStart(2, '0');
        let time = `${hours}:${minutes} ${ampm}`;

        let formattedDate = `${day}-${month}-${year} ${time}`;

        let message = emailTemplatesData.message || '';

        message = message.replace(/{username}/g, userName);
        message = message.replace(/{parcelid}/g, orderId);
        message = message.replace(/{date}/g, formattedDate);
        message = message.replace(/{sendername}/g, senderName);
        message = message.replace(/{senderphone}/g, senderphone);
        message = message.replace(/{note}/g, note);

        var url = "{{url('send-email')}}";

        return await sendEmail(url, emailTemplatesData.subject, message, [userEmail]);
    }

    function renderMailTaxSection(section, labelSuffix) {
        let taxHtml = "";
        if (!taxBreakdownGrouped[section]) return;
        for (let title in taxBreakdownGrouped[section]) {
            let taxlabel = title;
            let taxAmount = parseFloat(taxBreakdownGrouped[section][title]);
            taxHtml += `${taxlabel} ${labelSuffix} : ${formatCurrency(taxAmount, currencyData)}<br>`;
        }
        return taxHtml;
    }

    /* ------------------------------------------------------------------
     * Subscription purchase emails.
     *
     * Two templates, both edited by the client in the admin panel's Email
     * Templates screen rather than written into this code:
     *
     *   subscription_purchased        -> the customer
     *   subscription_purchased_admin  -> the admin
     *
     * Covers BOTH kinds of subscription - the platform's own order-history
     * plan and a plan a store sells - because a customer buying either has
     * paid for something and expects to be told.
     *
     * NOTHING HERE CAN BREAK A PURCHASE. Every path is wrapped, and a missing
     * template, a missing address or a failed send is logged and dropped. The
     * money has already moved by the time this runs; an email that does not
     * arrive must never make it look as though the purchase failed.
     * ------------------------------------------------------------------ */
    async function sendSubscriptionMail(details) {
        try {
            await sendOneSubscriptionMail('subscription_purchased', details, false);
            await sendOneSubscriptionMail('subscription_purchased_admin', details, true);
        } catch (err) {
            console.error('subscription email could not be sent', err);
        }
    }

    async function sendOneSubscriptionMail(type, details, toAdmin) {
        try {
            var snapshot = await database.collection('email_templates')
                .where('type', '==', type).limit(1).get();

            /* No template means no email - not a hardcoded English one, which
             * would go out in the wrong language and ignore the client's
             * wording. */
            if (snapshot.empty) {
                return;
            }

            var template = snapshot.docs[0].data();
            var subject = fillSubscriptionMailTokens(template.subject || '', details);
            var message = fillSubscriptionMailTokens(template.message || '', details);

            if (toAdmin) {
                /* The admin address lives in the server's configuration. The
                 * browser asks for it to be used; it never learns what it is. */
                await sendEmailToAdmin("{{ url('send-email') }}", subject, message);
                return;
            }

            if (!details.customerEmail) {
                return;
            }

            await sendEmail("{{ url('send-email') }}", subject, message, [details.customerEmail]);
        } catch (err) {
            console.error('subscription email (' + type + ') could not be sent', err);
        }
    }

    /* The same placeholders the other templates use, in the same {token}
     * style. A token the client has not used is simply absent; a token with
     * nothing behind it is emptied rather than left showing as {price}. */
    function fillSubscriptionMailTokens(text, details) {
        return String(text)
            .replace(/{username}/g, details.customerName || '')
            .replace(/{customeremail}/g, details.customerEmail || '')
            .replace(/{planname}/g, details.planName || '')
            .replace(/{plantype}/g, details.planType || '')
            .replace(/{storename}/g, details.storeName || '')
            .replace(/{price}/g, details.price || '')
            .replace(/{paymentmethod}/g, details.paymentMethod || '')
            .replace(/{expirydate}/g, details.expiryDate || '')
            .replace(/{date}/g, details.date || '');
    }

    async function sendEmailToAdmin(url, subject, message) {
        var checkFlag = false;
        await $.ajax({
            type: 'POST',
            data: {
                subject: subject,
                message: btoa(message),
                to_admin: 1
            },
            url: url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () { checkFlag = true; },
            error: function () { checkFlag = true; }
        });
        return checkFlag;
    }

    /* Gathers what the templates can show, for a plan a STORE sold. */
    async function storeSubscriptionMailDetails(plan, vendor, expiry) {
        var customer = null;

        try {
            var snapshot = await database.collection('users').doc(cuser_id).get();
            customer = snapshot.exists ? snapshot.data() : null;
        } catch (e) {}

        var currency = await getCurrencyForStore(vendor || { regionId: plan.regionId });

        return {
            customerName: customer
                ? ((customer.firstName || '') + ' ' + (customer.lastName || '')).trim()
                : '',
            customerEmail: customer ? (customer.email || '') : '',
            planName: plan.title || '',
            planType: "{{ trans('lang.store_subscriptions_title') }}",
            storeName: (vendor && vendor.title) ? vendor.title : '',
            price: formatCurrency(parseFloat(plan.price || 0) || 0, currency),
            paymentMethod: 'Wallet',
            expiryDate: expiry ? expiry.toDateString() : '',
            date: new Date().toDateString()
        };
    }

    async function sendEmail(url, subject, message, recipients) {
        var checkFlag = false;
        await $.ajax({
            type: 'POST',
            data: {
                subject: subject,
                message: btoa(message),
                recipients: recipients
            },
            url: url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                checkFlag = true;
            },
            error: function(xhr, status, error) {
                checkFlag = true;
            }
        });
        return checkFlag;
    }

    <?php if (@Route::current()->getName() == 'checkout') { ?>

        /* The suggestions themselves are bound by initAddressModalAutocomplete(),
         * which now runs on every page instead of only here. What is left is
         * the checkout-only prefill of the field from the known address. */
        function initializeCheckout() {
            if (address_name != '') {
                document.getElementById('address_line1').value = address_name;
            }
            initAddressModalAutocomplete();
        }

        $(function () {
            initializeCheckout();
        });
    <?php } ?>

    /* The map-pin button fails silently in the stock panel: the google branch
 * passes an empty error callback, and showError() writes into an <input>'s
 * innerHTML, which renders nothing at all. A customer clicking it just sees
 * the page do nothing.
 *
 * The commonest cause is not a refused permission. Browsers only expose
 * navigator.geolocation on SECURE origins, so the button can never work over
 * plain http on anything but localhost - which is how this panel is served in
 * development. Reporting that clearly saves a long hunt.
 *
 * Rather than leave a dead button, open the address modal so the customer can
 * type an address instead, and say why it opened. */
    function handleGeolocationFailure(error) {
        var message;
        if (!window.isSecureContext) {
            message = "{{ trans('lang.location_needs_https') }}";
        } else if (error && error.code === 1) {
            message = "{{ trans('lang.location_permission_denied') }}";
        } else {
            message = "{{ trans('lang.location_unavailable') }}";
        }

        /* The modal clears its own status on shown, so set ours after that. */
        $('#locationModalAddress').one('shown.bs.modal', function () {
            setTimeout(function () {
                addressModalStatus(message, true);
            }, 0);
        });

        var opener = document.getElementById('locationModal');
        if (opener) {
            opener.click();
        }
    }

    async function getCurrentLocation(type = '') {
        var is_map = '';
        is_map = "<?php echo env('IS_MAP'); ?>";
        if (mapType == 'google') {
            var geocoder = new google.maps.Geocoder();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(async function(position) {
                        var pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        var geolocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                        var circle = new google.maps.Circle({
                            center: geolocation,
                            radius: position.coords.accuracy
                        });
                        var location = new google.maps.LatLng(pos['lat'], pos['lng']);
                        geocoder.geocode({
                            'latLng': location
                        }, async function(results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                if (results.length > 0) {
                                    document.getElementById('user_locationnew').value = results[0].formatted_address;
                                    address_name1 = '';
                                    $.each(results[0].address_components, async function(i, address_component) {
                                        address_name1 = '';
                                        if (address_component.types[0] == "premise") {
                                            if (address_name1 == '') {
                                                address_name1 = address_component.long_name;
                                            } else {
                                                address_name2 = address_component.long_name;
                                            }
                                        } else if (address_component.types[0] == "postal_code") {
                                            address_zip = address_component.long_name;
                                        } else if (address_component.types[0] == "locality") {
                                            address_city = address_component.long_name;
                                        } else if (address_component.types[0] == "administrative_area_level_1") {
                                            address_state = address_component.long_name;
                                        } else if (address_component.types[0] == "country") {
                                            address_country = address_component.long_name;
                                        }
                                    });
                                    address_name = results[0].formatted_address;
                                    address_lat = results[0].geometry.location.lat();
                                    address_lng = results[0].geometry.location.lng();
                                    setCookie('address_name1', address_name1, 365);
                                    setCookie('address_name2', address_name2, 365);
                                    setCookie('address_name', address_name, 365);
                                    setCookie('address_lat', address_lat, 365);
                                    setCookie('address_lng', address_lng, 365);
                                    setCookie('address_zip', address_zip, 365);
                                    setCookie('address_city', address_city, 365);
                                    setCookie('address_state', address_state, 365);
                                    setCookie('address_country', address_country, 365);
                                    await setUserCountryCookie(address_lat, address_lng);
                                    if (type == 'reload') {
                                        window.location.reload(true);
                                    }
                                }
                            }
                        });
                        try {
                            if (autocomplete) {
                                autocomplete.setBounds(circle.getBounds());
                            }
                        } catch (err) {}
                    },
                    handleGeolocationFailure);
            } else {
                handleGeolocationFailure(null);
            }
        } else {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                handleGeolocationFailure(null);
            }
            if (type == 'reload') {
                window.location.reload(true);
            }
        }
    }
    function showPosition(position) {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        fetchNearbyPlaces(latitude, longitude);
    }
    function fetchNearbyPlaces(lat, lon) {
        const lat1 = lat.toFixed(4);
        const lon1 = lon.toFixed(4);
        const url = 'https://nominatim.openstreetmap.org/reverse?lat=' + lat1 + '&lon=' + lon1 + '&format=json&addressdetails=1';
        $.getJSON(url, async function(data) {
            if (data && data.address) {
                const placeName = data.display_name;
                $('#user_locationnew').val(placeName);
                var address_name = placeName;
                var address_lat = lat1;
                var address_lng = lon1;
                var address = placeName || {}; // Default to empty object if address is undefined
                // Extract address components from the selected place
                var address = data.address;
                var address_city = address.city || address.town || address.village || '';
                var address_state = address.state || '';
                var address_country = address.country || '';
                var address_zip = address.postcode || '';
                var address_name1 = address.road || '';
                var address_name2 = address.neighbourhood || address.suburb || '';
                // Set the cookies for the selected address details
                setCookie('address_name1', address_name1, 365);
                setCookie('address_name2', address_name2, 365);
                setCookie('address_name', address_name, 365);
                setCookie('address_lat', address_lat, 365);
                setCookie('address_lng', address_lng, 365);
                setCookie('address_zip', address_zip, 365);
                setCookie('address_city', address_city, 365);
                setCookie('address_state', address_state, 365);
                setCookie('address_country', address_country, 365);
                await setUserCountryCookie(address_lat, address_lng);
                if (type == 'reload') {
                    window.location.reload(true);
                }
            } else {
                console.error("Place not found.");
            }
        }).fail(function() {
            console.error("Error fetching data from Nominatim.");
        });
    }
    /* Was writing each message into an <input>'s innerHTML, which renders
 * nothing, so every geolocation failure on the OSM path was invisible. */
    function showError(error) {
        handleGeolocationFailure(error);
    }
    async function saveShippingAddress() {
        var line1 = $("#address_line1").val();
        var line2 = $("#address_line2").val();
        var city = $("#address_city").val();
        var country = $("#address_country").val();
        var postalCode = $("#address_zipcode").val();
        var full_address = '';
        if (cuser_id != "") {
            userDetailsRef.get().then(async function(userSnapshots) {
                /* NO MATCHING RECORD IS POSSIBLE FOR A SIGNED-IN VISITOR.
                 * cuser_id comes from the MySQL side; the document it points at
                 * lives in Firestore and can be missing - deleted from the admin
                 * panel while the customer is still signed in, or written without
                 * its own `id` field, which is what this query matches on.
                 *
                 * Reading docs[0] regardless threw "Cannot read properties of
                 * undefined" INSIDE A PROMISE WITH NO CATCH, so the address was
                 * silently not saved and the console carried an unhandled
                 * rejection. The address still belongs in the cookies, which is
                 * what the whole panel reads it from. */
                if (!userSnapshots.docs.length) {
                    console.warn('no users record for this account; address saved to cookies only');
                    await saveShippingAddressToCookies();
                    return;
                }

                var userDetails = userSnapshots.docs[0].data();
                if (userDetails.hasOwnProperty('shippingAddress')) {
                    var shippingAddress = userDetails.shippingAddress;
                    shippingAddress.line1 = $("#address_line1").val();
                    shippingAddress.line2 = $("#address_line2").val();
                    shippingAddress.city = $("#address_city").val();
                    shippingAddress.country = $("#address_country").val();
                    shippingAddress.postalCode = $("#address_zipcode").val();
                } else {
                    var shippingAddress = [];
                    var shippingAddress = {
                        "line1": line1,
                        "line2": line2,
                        "city": city,
                        "country": country,
                        "postalCode": postalCode
                    };
                }
                setCookie('address_name1', line1, 365);
                setCookie('address_name2', line2, 365);
                setCookie('address_lat', jQuery("#address_lat").val(), 365);
                setCookie('address_lng', jQuery("#address_lng").val(), 365);
                setCookie('address_zip', postalCode, 365);
                setCookie('address_city', city, 365);
                setCookie('address_country', country, 365);
                await setUserCountryCookie(jQuery("#address_lat").val(), jQuery("#address_lng").val());
                if (line1 != "") {
                    full_address = line1;
                }
                if (line2 != "") {
                    full_address = full_address + ',' + line2;
                }
                if (postalCode != "") {
                    full_address = full_address + ',' + postalCode;
                }
                if (city != "") {
                    full_address = full_address + ',' + city;
                }
                if (country != "") {
                    full_address = full_address + ',' + country;
                }
                setCookie('address_name', full_address, 365);
                database.collection('users').doc(cuser_id).update({
                    'shippingAddress': shippingAddress
                }).then(function(result) {
                    $('#close_button').trigger("click");
                    location.reload();
                }).catch(async function(error) {
                    /* The cookies are what every screen in this panel actually
                     * reads the address from, so a failed write to the customer's
                     * record must not leave them with no address at all. */
                    console.error('shipping address could not be saved to the account', error);
                    await saveShippingAddressToCookies();
                });
            }).catch(async function(error) {
                console.error('account record could not be read; address saved to cookies only', error);
                await saveShippingAddressToCookies();
            });
        } else {
            await saveShippingAddressToCookies();
        }
    }

    /* Writes the address to cookies and closes the window. This is the whole
     * of what a signed-out visitor's save does, and it is also the FALLBACK
     * for a signed-in one whose Firestore record cannot be read.
     *
     * Reads the fields again rather than taking them as arguments so there is
     * one place they are named, and no chance of the two callers drifting. */
    async function saveShippingAddressToCookies() {
        var line1 = $("#address_line1").val();
        var line2 = $("#address_line2").val();
        var city = $("#address_city").val();
        var country = $("#address_country").val();
        var postalCode = $("#address_zipcode").val();
        var full_address = '';

        setCookie('address_name1', line1, 365);
        setCookie('address_name2', line2, 365);
        setCookie('address_lat', jQuery("#address_lat").val(), 365);
        setCookie('address_lng', jQuery("#address_lng").val(), 365);
        setCookie('address_zip', postalCode, 365);
        setCookie('address_city', city, 365);
        setCookie('address_country', country, 365);
        await setUserCountryCookie(jQuery("#address_lat").val(), jQuery("#address_lng").val());
        if (line1 != "") {
            full_address = line1;
        }
        if (line2 != "") {
            full_address = full_address + ',' + line2;
        }
        if (postalCode != "") {
            full_address = full_address + ',' + postalCode;
        }
        if (city != "") {
            full_address = full_address + ',' + city;
        }
        if (country != "") {
            full_address = full_address + ',' + country;
        }
        setCookie('address_name', full_address, 365);
        $('#close_button').trigger("click");
        location.reload();
    }
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }
    
    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    function deleteCookie(name) {
        document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    }

</script>

<script type="text/javascript">
    
    <?php
    $user_email = '';
    $user_uuid = '';
    $auth_id = Auth::id();
    if ($auth_id) {
        $user = App\Models\User::select('email')->where('id', $auth_id)->first();
        $user_email = $user->email;
        $user_uuid = App\Models\VendorUsers::select('uuid')->where('email', $user_email)->first();
        $user_uuid = $user_uuid->uuid;
    }
    ?>
    var database = firebase.firestore();
    var placeholderImageHeader = '';
    var googleMapKeySettingHeader = database.collection('settings').doc("googleMapKey");
    googleMapKeySettingHeader.get().then(async function(googleMapKeySnapshotsHeader) {
        var placeholderImageHeaderData = googleMapKeySnapshotsHeader.data();

        if (placeholderImageHeaderData) {
            placeholderImageHeader = placeholderImageHeaderData.placeHolderImage;
        }
    })
    var user_email = "<?php echo $user_email; ?>";
    var user_ref = '';
    var referral_ref = '';
    if (user_email != '') {
        var user_uuid = "<?php echo $user_uuid; ?>";
        user_ref = database.collection('users').where("id", "==", user_uuid);
        referral_ref = database.collection('referral').doc(user_uuid);
    }
    var ref = database.collection('settings').doc("globalSettings");
    ref.get().then(async function(snapshots) {
        var globalSettings = snapshots.data();

        if (globalSettings) {
            $("#logo_web").attr('src', globalSettings.appLogo);
            $("#footer_logo_web").attr('src', globalSettings.appLogo);
        }
    });

    $(document).ready(async function() {

        jQuery("#data-table_processing").show();

        /* Drops any payment method the customer's region does not carry, on
         * every screen that offers them. Never blocks the page: a failure
         * leaves the methods as they were. */
        try {
            await enforcePaymentMethodRegions();
        } catch (e) {
            console.error('payment method regions could not be applied', e);
        }

         if(getCookie('section_id')){
            let sectionRef = await database.collection('sections').doc(getCookie('section_id')).get();
            var adminCommissionSettings = sectionRef.data();

            /* A SECTION COOKIE CAN OUTLIVE THE SECTION. It is kept for a year,
             * and the section behind it can be deleted or renamed in the admin
             * panel meanwhile - .data() is then undefined and reading
             * .adminCommision off it threw.
             *
             * This sits near the TOP of the ready handler, so that throw took
             * everything below it with it, including the block that draws the
             * signed-in customer's name and avatar. A stale cookie must not
             * cost a visitor their header. */
            if (adminCommissionSettings) {
                localStorage.setItem('adminCommissionSettings', JSON.stringify(adminCommissionSettings.adminCommision));
                localStorage.setItem('platformFeeSettings', JSON.stringify(adminCommissionSettings.platformFee));
                localStorage.setItem('packagingChargeEnable', adminCommissionSettings.packagingChargeEnable);
            } else {
                console.warn('section cookie points at a section that no longer exists');
            }
        }
        
        if (user_ref != '') {
            user_ref.get().then(async function(profileSnapshots) {
                /* AN EMPTY RESULT LEAVES THE ACCOUNT BUTTON BLANK, and a blank
                 * dropdown-toggle has nothing to click - a signed-in customer
                 * whose Firestore record is missing loses the whole account
                 * menu, with no sign-in link either, because Blade already knows
                 * they are signed in. Give the button a label so the menu stays
                 * reachable. */
                if (!profileSnapshots.docs.length) {
                    console.warn('no users record for this account; account menu shown without a name');
                    $("#dropdownMenuButton").append('<img alt="#" src="' + placeholderImage + '" class="img-fluid rounded-circle header-user mr-2 header-user">' + "{{ trans('lang.my_account') }}");
                }

                if (profileSnapshots.docs.length) {
                    var profile_user = profileSnapshots.docs[0].data();
                    var profile_name = profile_user.firstName + " " + profile_user.lastName;
                    if (profile_user.profilePictureURL != '' && profile_user.profilePictureURL != null) {
                        $("#dropdownMenuButton").append('<img alt="#" src="' + profile_user.profilePictureURL + '" class="img-fluid rounded-circle header-user mr-2 header-user">Hi ' + profile_user.firstName);
                    } else {
                        $("#dropdownMenuButton").append('<img alt="#" src="' + placeholderImage + '" class="img-fluid rounded-circle header-user mr-2 header-user">Hi ' + profile_user.firstName);
                    }
                    if (profile_user.shippingAddress) {
                        $("#user_location").html(profile_user.shippingAddress.city);
                    }
                }
            })
        }
        if (referral_ref) {
            referral_ref.get().then(async function(refSnapshot) {
                var referral_data = refSnapshot.data();
                if (referral_data != undefined && referral_data.referralCode != null && referral_data.referralCode != undefined) {
                    $(".referral_code").html("<b>{{ trans('lang.your_referral_code') }} : " + referral_data.referralCode + "</b>");
                }
            })
        }
    });

    $(".user-logout-btn").click(async function() {
        firebase.auth().signOut().then(function() {
            var logoutURL = "{{ route('logout') }}";
            $.ajax({
                type: 'POST',
                url: logoutURL,
                data: {},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data1) {
                    if (data1.logoutuser) {
                        window.location = "{{ route('login') }}";
                    }
                }
            })
        });
    });

    $(document).ready(function() {

        $(document).on("click", ".select_section", async function(e) {
            var section_id = $(this).attr('data-id');
            var section_name = $(this).attr('data-name');
            var section_color = $(this).attr('data-color');
            var dine_in_active = $(this).attr('data-dine_in');
            var service_type = $(this).attr('service_type');
            if (dine_in_active != 'true') {
                dine_in_active = 'false';
            }
            if (getCookie('service_type') == "Parcel Delivery Service" || getCookie('service_type') == "Rental Service" || getCookie('service_type') == "Cab Service" || getCookie('service_type') == "On Demand Service") {
                setCookie('section_id', section_id, 365);
                setCookie('section_name', section_name, 365);
                setCookie('section_color', section_color, 365);
                setCookie('dine_in_active', dine_in_active.toString(), 365);
                setCookie('service_type', service_type, 365);
                window.location.href = "<?php echo url('/'); ?>";
            } else {
                await $.ajax({
                    url: 'check-cart-data',
                    type: 'GET',
                    success: async function(result) {
                        if (result > 0) {
                            Swal.fire({
                                text: "{{ trans('lang.section_change_alert') }}",
                                icon: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Yes, change it!"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        data: {
                                            "_token": "{{ csrf_token() }}",
                                        },
                                        url: 'remove-cart-data',
                                        type: 'POST',
                                        success: function(result) {
                                            setCookie('section_id', section_id, 365);
                                            setCookie('section_name', section_name, 365);
                                            setCookie('section_color', section_color, 365);
                                            setCookie('dine_in_active', dine_in_active.toString(), 365);
                                            setCookie('service_type', service_type, 365);
                                            window.location.href = "<?php echo url('/'); ?>";
                                        }
                                    });
                                }
                            });
                        } else {
                            setCookie('section_id', section_id, 365);
                            setCookie('section_name', section_name, 365);
                            setCookie('section_color', section_color, 365);
                            setCookie('dine_in_active', dine_in_active.toString(), 365);
                            setCookie('service_type', service_type, 365);
                            window.location.href = "<?php echo url('/'); ?>";
                        }
                    }
                });
            }
        });
    });
</script>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="{{ asset('js/rocket-loader.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('https://static.cloudflareinsights.com/beacon.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/sweetalert2.js') }}"></script>

<?php if (Auth::user()) { ?>

<script type="text/javascript">
    var route1 = '<?php echo route('my_order'); ?>';
    var routeparcel = '<?php echo route('parcel_orders'); ?>';
    var routerental = '<?php echo route('rental_orders'); ?>';
    var routeondemand = '<?php echo route('my-bookings'); ?>';
    var orderAcceptedSubject = '';
    var orderAcceptedMsg = '';
    var orderRejectedSubject = '';
    var orderRejectedMsg = '';
    var orderCompletedSubject = '';
    var orderCompletedMsg = '';
    var storeOrderCompletedSubject = '';
    var storeOrderCompletedMsg = '';
    var storeOrderAcceptedSubject = '';
    var storeOrderAcceptedMsg = '';
    var takeAwayOrderCompletedSubject = '';
    var takeAwayOrderCompletedMsg = '';
    var driverAcceptedSubject = '';
    var driverAcceptedMsg = '';
    var dineInAcceptedSubject = '';
    var dineInAcceptedMsg = '';
    var dineInRejectedSubject = '';
    var dineInRejectedMsg = '';
    var parcelCompletedSubject = '';
    var parcelCompletedMsg = '';
    var cabAccepetedSubject = '';
    var cabAccepetedMsg = '';
    var cabCompletedSubject = '';
    var cabCompletedMsg = '';
    var parcelAccepetedSubject = '';
    var parcelAccepetedMsg = '';
    var parcelRejectedSubject = '';
    var parcelRejectedMsg = '';
    var rentalRejectedSubject = '';
    var rentalRejectedMsg = '';
    var rentalAccepetedSubject = '';
    var rentalAccepetedMsg = '';
    var startRideSubject = '';
    var startRideMsg = '';
    var rentalCompletedSubject = '';
    var rentalCompletedMsg = '';
    var storeOrderInTransitSubject = "";
    var storeOrderInTransitMsg = "";
    var bookingAcceptedSubject = '';
    var bookingAcceptedMsg = '';
    var bookingRejectedSubject = '';
    var bookingRejectedMsg = '';
    var bookingInTransitSubject = '';
    var bookingInTransitdMsg = '';
    var bookingCompletedSubject = '';
    var bookingCompletedMsg = '';
    var bookingAddExtraChargeSub = '';
    var bookingAddExtraChargeMsg = '';
    var bookingEndSubject = '';
    var bookingEnddMsg = '';
    var database = firebase.firestore();
    database.collection('dynamic_notification').get().then(async function(snapshot) {
        if (snapshot.docs.length > 0) {
            snapshot.docs.map(async (listval) => {
                val = listval.data();
                if (val.type == "driver_accepted") {
                    driverAcceptedSubject = val.subject;
                    driverAcceptedMsg = val.message;
                } else if (val.type == "restaurant_rejected") {
                    orderRejectedSubject = val.subject;
                    orderRejectedMsg = val.message;
                } else if (val.type == "takeaway_completed") {
                    takeAwayOrderCompletedSubject = val.subject;
                    takeAwayOrderCompletedMsg = val.message;
                } else if (val.type == "driver_completed") {
                    orderCompletedSubject = val.subject;
                    orderCompletedMsg = val.message;
                } else if (val.type == "store_completed") {
                    storeOrderCompletedSubject = val.subject;
                    storeOrderCompletedMsg = val.message;
                } else if (val.type == "store_accepted") {
                    storeOrderAcceptedSubject = val.subject;
                    storeOrderAcceptedMsg = val.message;
                } else if (val.type == "store_intransit") {
                    storeOrderInTransitSubject = val.subject;
                    storeOrderInTransitMsg = val.message;
                } else if (val.type == "restaurant_accepted") {
                    orderAcceptedSubject = val.subject;
                    orderAcceptedMsg = val.message;
                } else if (val.type == "dinein_accepted") {
                    dineInAcceptedSubject = val.subject;
                    dineInAcceptedMsg = val.message;
                } else if (val.type == "dinein_canceled") {
                    dineInRejectedSubject = val.subject;
                    dineInRejectedMsg = val.message;
                } else if (val.type == "cab_accepted") {
                    cabAccepetedSubject = val.subject;
                    cabAccepetedMsg = val.message;
                } else if (val.type == "cab_completed") {
                    cabCompletedSubject = val.subject;
                    cabCompletedMsg = val.message;
                } else if (val.type == "parcel_accepted") {
                    parcelAccepetedSubject = val.subject;
                    parcelAccepetedMsg = val.message;
                } else if (val.type == "parcel_rejected") {
                    parcelRejectedSubject = val.subject;
                    parcelRejectedMsg = val.message;
                } else if (val.type == "rental_rejected") {
                    rentalRejectedSubject = val.subject;
                    rentalRejectedMsg = val.message;
                } else if (val.type == "rental_accepted") {
                    rentalAccepetedSubject = val.subject;
                    rentalAccepetedMsg = val.message;
                } else if (val.type == "start_ride") {
                    startRideSubject = val.subject;
                    startRideMsg = val.message;
                } else if (val.type == "rental_completed") {
                    rentalCompletedSubject = val.subject;
                    rentalCompletedMsg = val.message;
                } else if (val.type == "parcel_completed") {
                    parcelCompletedSubject = val.subject;
                    parcelCompletedMsg = val.message;
                } else if (val.type == "provider_accepted") {
                    bookingAcceptedSubject = val.subject;
                    bookingAcceptedMsg = val.message;
                } else if (val.type == "provider_rejected") {
                    bookingRejectedSubject = val.subject;
                    bookingRejectedMsg = val.message;
                } else if (val.type == "service_intransit") {
                    bookingInTransitSubject = val.subject;
                    bookingInTransitdMsg = val.message;
                } else if (val.type == "service_completed") {
                    bookingCompletedSubject = val.subject;
                    bookingCompletedMsg = val.message;
                } else if (val.type == "service_charges") {
                    bookingAddExtraChargeSub = val.subject;
                    bookingAddExtraChargeMsg = val.message;
                } else if (val.type == "stop_time") {
                    bookingEndSubject = val.subject;
                    bookingEnddMsg = val.message;
                }
            });
        }
    });
    var pageloadded = 0;
    database.collection('vendor_orders').where('author.id', "==", cuser_id).onSnapshot(function(doc) {
        if (pageloadded) {
            doc.docChanges().forEach(function(change) {
                val = change.doc.data();
                if (change.type == "modified") {
                    if (val.status == "Order Completed" && val.takeAway == true || val.takeAway == 'true') {
                        $('.order_notification_title').text(takeAwayOrderCompletedSubject);
                        $('.order_notification_message').html(takeAwayOrderCompletedMsg);
                        $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Order Completed" && val.takeAway == false || val.takeAway == 'false') {
                        if (section_id == val.section_id) {
                            if (getCookie('service_type') == "Ecommerce Service") {
                                $('.order_notification_title').text(storeOrderCompletedSubject);
                                $('.order_notification_message').html(storeOrderCompletedMsg);
                            } else if (getCookie('service_type') == "Multivendor Delivery Service") {
                                $('.order_notification_title').text(orderCompletedSubject);
                                $('.order_notification_message').html(orderCompletedMsg);
                            }
                            $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    } else if (val.status == "In Transit" && getCookie('service_type') == "Ecommerce Service") {
                        $('.order_notification_title').text(storeOrderInTransitSubject);
                        $('.order_notification_message').html(storeOrderInTransitMsg);
                        $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Order Accepted") {
                        if (section_id == val.section_id) {
                            if (getCookie('service_type') == "Multivendor Delivery Service") {
                                $('.order_notification_title').text(orderAcceptedSubject);
                                $('.order_notification_message').html(orderAcceptedMsg);
                            } else if (getCookie('service_type') == "Ecommerce Service") {
                                $('.order_notification_title').text(storeOrderAcceptedSubject);
                                $('.order_notification_message').html(storeOrderAcceptedMsg);
                            }
                            $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    } else if (val.status == "Driver Accepted" && getCookie('service_type') == "Multivendor Delivery Service") {
                        $('.order_notification_title').text(driverAcceptedSubject);
                        $('.order_notification_message').html(driverAcceptedMsg);
                        $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Order Rejected" && getCookie('service_type') == "Multivendor Delivery Service") {
                        $('.order_notification_title').text(orderRejectedSubject);
                        $('.order_notification_message').html(orderRejectedMsg);
                        $("#order_notification_url").attr("href", route1.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    }
                }
            });
        } else {
            pageloadded = 1;
        }
    });
    var ondemandPageLoadded = 0;
    database.collection('provider_orders').where('author.id', "==", cuser_id).onSnapshot(function(doc) {
        if (ondemandPageLoadded) {
            doc.docChanges().forEach(function(change) {
                val = change.doc.data();
                if (change.type == "modified") {
                    if (val.status == "Order Accepted") {
                        if (section_id == val.sectionId) {
                            $('.order_notification_title').text(bookingAcceptedSubject);
                            $('.order_notification_message').html(bookingAcceptedMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    } else if (val.status == "Order Rejected") {
                        if (section_id == val.sectionId) {
                            $('.order_notification_title').text(bookingRejectedSubject);
                            $('.order_notification_message').html(bookingRejectedMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    } else if (val.status == "Order Ongoing" && val.extraCharges == "" && val.endTime == null) {
                        if (section_id == val.sectionId) {
                            $('.order_notification_title').text(bookingInTransitSubject);
                            $('.order_notification_message').html(bookingInTransitdMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    } else if (val.status == 'Order Ongoing' && val.extraCharges != "" && val.extraCharges != null && sessionStorage.getItem('extra_charge_notiifcation_' + val.id) == null) {
                        if (section_id == val.sectionId) {
                            $('.order_notification_title').text(bookingAddExtraChargeSub);
                            $('.order_notification_message').html(bookingAddExtraChargeMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                            sessionStorage.setItem('extra_charge_notiifcation_' + val.id, true);
                        }
                    } else if (val.status == "Order Ongoing" && val.endTime != null && sessionStorage.getItem('stop_time_notiifcation_' + val.id) == null) {
                        if (section_id == val.sectionId) {
                            $('.order_notification_title').text(bookingEndSubject);
                            $('.order_notification_message').html(bookingEnddMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                            sessionStorage.setItem('stop_time_notiifcation_' + val.id, true);
                        }
                    } else if (val.status == "Order Completed") {
                        if (section_id == val.sectionId) {
                            sessionStorage.removeItem('stop_time_notiifcation_' + val.id);
                            sessionStorage.removeItem('extra_charge_notiifcation_' + val.id);
                            $('.order_notification_title').text(bookingCompletedSubject);
                            $('.order_notification_message').html(bookingCompletedMsg);
                            $("#order_notification_url").attr("href", routeondemand.replace(':id', val.id));
                            $("#order_notification_modal").trigger("click");
                        }
                    }
                }
            })
        } else {
            ondemandPageLoadded = 1;
        }
    });
    var parcel_page_loaded = 0;
    var addParcelReviewBtnClicked = false;
    database.collection('parcel_orders').where('author.id', "==", cuser_id).onSnapshot(function(doc) {
        if (parcel_page_loaded) {
            doc.docChanges().forEach(function(change) {
                val = change.doc.data();
                if (change.type == "modified") {
                    if (val.status == "Order Completed" && addParcelReviewBtnClicked == false) {
                        $('.order_notification_title').text(parcelCompletedSubject);
                        $('.order_notification_message').text(parcelCompletedMsg);
                        $("#order_notification_url").attr("href", routeparcel.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Driver Accepted") {
                        $('.order_notification_title').text(parcelAccepetedSubject);
                        $('.order_notification_message').text(parcelAccepetedMsg);
                        $("#order_notification_url").attr("href", routeparcel.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Order Rejected") {
                        $('.order_notification_title').text(parcelRejectedSubject);
                        $('.order_notification_message').text(parcelRejectedMsg);
                        $("#order_notification_url").attr("href", routeparcel.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    }
                }
            });
        } else {
            parcel_page_loaded = 1;
        }
    });
    var rental_page_loaded = 0;
    var addRentalReviewBtnClicked = false;
    database.collection('rental_orders').where('author.id', "==", cuser_id).onSnapshot(function(doc) {
        if (rental_page_loaded) {
            doc.docChanges().forEach(function(change) {
                val = change.doc.data();
                if (change.type == "modified") {
                    if (val.status == "Order Completed" && addRentalReviewBtnClicked == false) {
                        $('.order_notification_title').text(rentalCompletedSubject);
                        $('.order_notification_message').text(rentalCompletedMsg);
                        $("#order_notification_url").attr("href", routerental.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Driver Accepted") {
                        $('.order_notification_title').text(rentalAccepetedSubject);
                        $('.order_notification_message').text(rentalAccepetedMsg);
                        $("#order_notification_url").attr("href", routerental.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "In Transit") {
                        $('.order_notification_title').text(startRideSubject);
                        $('.order_notification_message').text(startRideMsg);
                        $("#order_notification_url").attr("href", routerental.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    } else if (val.status == "Order Rejected") {
                        $('.order_notification_title').text(rentalRejectedSubject);
                        $('.order_notification_message').text(rentalRejectedMsg);
                        $("#order_notification_url").attr("href", routerental.replace(':id', val.id));
                        $("#order_notification_modal").trigger("click");
                    }
                }
            });
        } else {
            rental_page_loaded = 1;
        }
    });
    var pageloadded_dining = 0;
    database.collection('booked_table').where('author.id', "==", cuser_id).onSnapshot(function(doc) {
        if (pageloadded_dining) {
            doc.docChanges().forEach(function(change) {
                val = change.doc.data();
                if (change.type == "modified") {
                    if (val.status == "Order Accepted") {
                        $('.dinein_order_notification_title').text(dineInAcceptedSubject);
                        $('.dinein_order_notification_message').text(dineInAcceptedMsg);
                        $("#dinein_order_notification_modal").trigger("click");
                    } else if (val.status == "Order Rejected") {
                        $('.dinein_order_notification_title').text(dineInRejectedSubject);
                        $('.dinein_order_notification_message').text(dineInRejectedMsg);
                        $("#dinein_order_notification_modal").trigger("click");
                    }
                }
            });
        } else {
            pageloadded_dining = 1;
        }
    });
    async function getDriver(driverData) {
        var rideDetails = '';
        var client_name = '';
        await database.collection('users').where("id", "==", driverData).get().then(async function(snapshotss) {
            if (snapshotss.docs[0]) {
                var ride_data = snapshotss.docs[0].data();
                client_name = ride_data.firstName;
                $('.accept_name').html($("<span id='np_accept_name'></span>").text(client_name));
                $('.driver_name').html($("<span id='restaurnat_name_1'></span>").text(client_name));
            } else {
                $('.accept_name').html($("<span id='np_accept_name'></span>").text(''));
                $('.driver_name').html($("<span id='restaurnat_name_1'></span>").text(''));
            }
        });
        return client_name;
    }
    async function getRentalDriver(driverData) {
        var rideDetails = '';
        var client_name = '';
        $('.driver_name_').empty('');
        await database.collection('users').where("id", "==", driverData).get().then(async function(snapshotss) {
            if (snapshotss.docs[0]) {
                var ride_data = snapshotss.docs[0].data();
                client_name = ride_data.firstName;
                $('.accept_name_').html($("<span id='np_accept_name'></span>").text(client_name));
                $('.driver_name_').html($("<span id='rental_name_2'></span>").text(client_name));
            } else {
                $('.accept_name_').html($("<span id='np_accept_name'></span>").text(''));
                $('.driver_name_').html($("<span id='restaurnat_name_2'></span>").text(''));
            }
        });
        return client_name;
    }
</script>
<?php } ?>

<script type="text/javascript">
    var langcount = 0;
    var languages_list_main = [];
    var languages_list = database.collection('settings').doc('languages');
    languages_list.get().then(async function(snapshotslang) {
        snapshotslang = snapshotslang.data();
        if (snapshotslang != undefined) {
            snapshotslang = snapshotslang.list;
            languages_list_main = snapshotslang;
            snapshotslang.forEach((data) => {
                if (data.isActive == true) {
                    langcount++;
                    $('#language_dropdown').append($("<option></option>").attr("value", data.slug).text(data.title));
                    $('#language_dropdown2').append($("<option></option>").attr("value", data.slug).text(data.title));
                }
            });
            if (langcount > 1) {
                $("#language_dropdown_box").css('visibility', 'visible');
            }
            <?php if (session()->get('locale')) { ?>
            $("#language_dropdown").val("<?php echo session()->get('locale'); ?>");
            $("#language_dropdown2").val("<?php echo session()->get('locale'); ?>");
            <?php } ?>
        }
    });
    var url = "{{ route('changeLang') }}";
    $(".changeLang").change(function() {
        var slug = $(this).val();
        languages_list_main.forEach((data) => {
            if (slug == data.slug) {
                if (data.is_rtl == undefined) {
                    setCookie('is_rtl', 'false', 365);
                } else {
                    setCookie('is_rtl', data.is_rtl.toString(), 365);
                }
                window.location.href = url + "?lang=" + slug;
            }
        });
    });
    $(document).ready(function() {
        var $main_nav = $('#main-nav');
        var $toggle = $('.toggle');
        var defaultOptions = {
            disableAt: false,
            customToggle: $toggle,
            levelSpacing: 40,
            navTitle: '<?php echo @$_COOKIE['section_name']; ?> - <?php echo env('APP_NAME'); ?>',
            levelTitles: true,
            levelTitleAsBack: true,
            pushContent: '#container',
            insertClose: 2
        };
        var Nav = $main_nav.hcOffcanvasNav(defaultOptions);
    });
    database.collection('settings').doc("notification_setting").get().then(async function(snapshots) {
        var data = snapshots.data();
        if (data != undefined) {
            serviceJson = data.serviceJson;
            if (serviceJson != '' && serviceJson != null) {
                $.ajax({
                    type: 'POST',
                    data: {
                        serviceJson: btoa(serviceJson),
                    },
                    url: "{{ route('storeServiceFile') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        checkFlag = true;
                    }
                });
            }
        }
    });
    //start - Get user zone id from address
    getUserZoneId();
    var user_zone_id = null;
    async function getUserZoneId(latitude='',longitude='') {
        const snapshots = await database.collection('zone').where("publish", "==", true).get();
        for (const snapshot of snapshots.docs) {
            const zone = snapshot.data();

            /* A ZONE SAVED WITHOUT ITS POLYGON STOPS THE LOOP DEAD, and this
             * function decides the delivery zone for EVERY visitor. Skip the
             * broken one rather than lose the rest - the zone screen was
             * rewritten on 29 Sep, so a half-saved zone is not hypothetical. */
            if (!zone || !Array.isArray(zone.area)) {
                continue;
            }

            const vertices_x = [];
            const vertices_y = [];
            for (const point of zone.area) {
                vertices_x.push(point.longitude);
                vertices_y.push(point.latitude);
            }
            if(latitude && longitude){
                address_lat = latitude;
                address_lng = longitude;
            }
            if (is_in_polygon(vertices_x, vertices_y, address_lng, address_lat)) {
                user_zone_id = zone.id;
                return user_zone_id;
            }
        }
        return user_zone_id;
    }
    function is_in_polygon(vertx, verty, testx, testy) {
        let c = false;
        let j = vertx.length - 1;
        for (let i = 0; i < vertx.length; i++) {
            if (
                (verty[i] > testy) != (verty[j] > testy) &&
                testx < ((vertx[j] - vertx[i]) * (testy - verty[i])) / (verty[j] - verty[i]) + vertx[i]
            ) {
                c = !c;
            }
            j = i;
        }
        return c;
    }
    
    function pointOnEdge(lat,lng,area){
        for(let i=0;i<area.length;i++){

            let a = area[i];
            let b = area[(i+1)%area.length];

            let cross =
                (lng-a.longitude)*(b.latitude-a.latitude) -
                (lat-a.latitude)*(b.longitude-a.longitude);

            if(Math.abs(cross) > 1e-6) continue;

            let dot =
                (lng-a.longitude)*(b.longitude-a.longitude) +
                (lat-a.latitude)*(b.latitude-a.latitude);

            if(dot < 0) continue;

            let sqLen =
                (b.longitude-a.longitude)**2 +
                (b.latitude-a.latitude)**2;

            if(dot <= sqLen)
                return true;
        }
        return false;
    }

    function pointOnVertex(lat,lng,area){
        return area.some(p =>
            Math.abs(p.latitude-lat) < 1e-6 &&
            Math.abs(p.longitude-lng) < 1e-6
        );
    }
    //end - Get user zone id from address
    //start - Get product price with admin commission globally
   
    function getFormattedPrice(price) {
        if (price != null && price !== "") {
            let final_price = price;
            // Format the final price based on the currency settings
            let formatted_price = currencyAtRight ?
                final_price.toFixed(decimal_degits) + "" + currentCurrency :
                currentCurrency + "" + final_price.toFixed(decimal_degits);
            return formatted_price;
        } else {
            return ''; // Return an empty string or handle case where price is not valid
        }
    }
    //end - Get product price with admin commission globally
    // Process each vendor's data and calculate the price with admin commission
    /* ------------------------------------------------------------------
     * Payment methods by region.
     *
     * The admin panel lets each gateway name the regions it is offered in
     * (Settings -> Payment Methods -> Available in Regions). THE WEBSITE WAS
     * IGNORING IT ENTIRELY - every screen showed a gateway on `isEnabled`
     * alone, so the setting had no effect on a customer.
     *
     * Nine screens offer gateways - checkout, wallet top-up, gift cards,
     * parcel, two rental screens, on-demand, extra charge and service charge -
     * with 109 places between them that reveal one. Rather than edit every
     * one, the rule is applied ONCE here: an option the region excludes is
     * removed from the page.
     *
     * Removal is why this is safe whatever the order. Each screen reveals its
     * gateways inside its own Firestore callback, so there is no reliable
     * moment "after" them; but `$('#x_box').show()` on an element that is no
     * longer in the document quietly does nothing. Removing early and removing
     * late both end with the option gone.
     *
     * FAILS OPEN. A settings document that cannot be read leaves the method
     * showing: a method that turns out not to apply is a failed payment, while
     * hiding them all is a customer who cannot pay at all.
     * ------------------------------------------------------------------ */
    var PAYMENT_METHOD_SETTINGS = {
        'cod_box': 'CODSettings',
        'wallet_box': 'walletSettings',
        'razorpay_box': 'razorpaySettings',
        'stripe_box': 'stripeSettings',
        'paypal_box': 'paypalSettings',
        'payfast_box': 'payFastSettings',
        'paystack_box': 'payStack',
        'flutterWave_box': 'flutterWave',
        'mercadopago_box': 'MercadoPago',
        'xendit_box': 'xendit_settings',
        'midtrans_box': 'midtrans_settings',
        'orangepay_box': 'orange_money_settings'
    };

    async function enforcePaymentMethodRegions() {
        var ids = Object.keys(PAYMENT_METHOD_SETTINGS).filter(function (id) {
            return document.getElementById(id) !== null;
        });

        /* Not a screen that offers payment methods. */
        if (ids.length === 0) {
            return;
        }

        var regionId = await getActiveRegionId();

        /* An unresolved region shows everything, the same way an unresolved
         * region shows every store rather than none. */
        if (!regionId) {
            return;
        }

        await Promise.all(ids.map(async function (id) {
            try {
                var doc = await database.collection('settings')
                    .doc(PAYMENT_METHOD_SETTINGS[id]).get();

                if (!doc.exists) {
                    return;
                }

                var regionIds = doc.data().regionIds;

                /* Empty or absent means offered everywhere - the same rule
                 * sections and subscription plans use. */
                if (!Array.isArray(regionIds) || regionIds.length === 0) {
                    return;
                }

                if (regionIds.indexOf(regionId) === -1) {
                    $('#' + id).remove();
                }
            } catch (e) {
                console.error('payment method regions could not be read for ' + id, e);
            }
        }));
    }

    /* ------------------------------------------------------------------
     * Buying a store's subscription, and paying for it by any method.
     *
     * The wallet buys one outright. Any other method goes through the wallet
     * TOP-UP that already exists - twelve gateways that are already built,
     * already tested and already region-filtered - and the subscription
     * completes by itself when the customer comes back.
     *
     * The alternative was an eighth copy of those twelve integrations, one
     * that could not be tested without live keys for every gateway. This way
     * the customer still picks any method their region carries, and the money
     * has somewhere safe to sit if anything goes wrong on the way back: it is
     * in their wallet, not lost.
     *
     * Lives here rather than in the store page because two screens need it -
     * the store page, and the top-up success page that finishes the job.
     * ------------------------------------------------------------------ */
    var STORE_SUBSCRIPTION_INTENT = 'pendingStoreSubscription';

    function saveStoreSubscriptionIntent(plan, returnUrl) {
        try {
            localStorage.setItem(STORE_SUBSCRIPTION_INTENT, JSON.stringify({
                planId: plan.id,
                vendorID: plan.vendorID,
                /* Kept only to notice a price change on the way back. The
                 * charge itself is always taken from the plan as it reads at
                 * that moment, never from this. */
                price: parseFloat(plan.price || 0) || 0,
                returnUrl: returnUrl || '',
                savedAt: Date.now()
            }));
        } catch (e) {
            console.error('subscription intent could not be saved', e);
        }
    }

    function readStoreSubscriptionIntent() {
        try {
            var raw = localStorage.getItem(STORE_SUBSCRIPTION_INTENT);
            if (!raw) {
                return null;
            }

            var intent = JSON.parse(raw);

            /* An intent older than an hour is stale - a customer who wandered
             * off mid-payment should not be charged when they return
             * tomorrow. */
            if (!intent || !intent.planId || (Date.now() - (intent.savedAt || 0)) > 3600000) {
                clearStoreSubscriptionIntent();
                return null;
            }

            return intent;
        } catch (e) {
            return null;
        }
    }

    function clearStoreSubscriptionIntent() {
        try {
            localStorage.removeItem(STORE_SUBSCRIPTION_INTENT);
        } catch (e) {}
    }

    /* Document 1: "The application's commission must also be deducted from
     * this service." The customer pays the store's price and the platform's
     * cut comes OUT of it - unlike a product, where the commission is added on
     * top of the vendor's price.
     *
     * The store's own rate when it has one, otherwise the section's, and
     * nothing at all when commission is switched off. */
    async function storePlanCommission(vendor, price) {
        var settings = localStorage.getItem('adminCommissionSettings');
        if (!settings) {
            return 0;
        }

        try {
            settings = JSON.parse(settings);
        } catch (e) {
            return 0;
        }

        if (!settings || settings.enable !== true) {
            return 0;
        }

        var rate = (vendor && vendor.adminCommission) ? vendor.adminCommission : settings;
        var amount = parseFloat(rate.commission) || 0;
        var commission = (rate.type === 'percentage') ? (price * amount / 100) : amount;

        /* Never more than the price itself - a fixed cut larger than a cheap
         * plan would otherwise hand the store a negative earning. */
        return Math.min(Math.max(commission, 0), price);
    }

    /* One Firestore transaction for the whole purchase. The balance is re-read
     * inside it and the purchase rejected if it moved, so a double click or a
     * second tab cannot pay twice.
     *
     * Four writes, all or none:
     *   users/{customerId}            the debit, and NOTHING else
     *   wallet/{new}                  the customer's own ledger line
     *   vendor_subscriptions/{new}    the Subscribers tab in both panels
     *   vendor_subscription_payments  the Payments tab in both panels
     *
     * The customer document gets the debit and no more. subscriptionPlanId and
     * its neighbours belong to the PLATFORM's own plan; a store plan must
     * never overwrite them, or a bakery's bread plan would silently replace
     * someone's order-history plan. */
    async function purchaseStorePlanFromWallet(plan, vendor) {
        var price = parseFloat(plan.price || 0) || 0;
        var walletId = database.collection('tmp').doc().id;
        var subscriptionId = database.collection('tmp').doc().id;
        var paymentId = database.collection('tmp').doc().id;
        var commission = await storePlanCommission(vendor, price);

        await database.runTransaction(async function (tx) {
            var userRef = database.collection('users').doc(cuser_id);
            var snapshot = await tx.get(userRef);
            var user = snapshot.exists ? snapshot.data() : {};

            var balance = parseFloat(user.wallet_amount || 0) || 0;
            if (balance < price) {
                throw new Error('INSUFFICIENT');
            }

            tx.update(userRef, { 'wallet_amount': balance - price });

            var expiry = null;
            if (String(plan.expiryDay) !== '-1') {
                var expiryDate = new Date();
                expiryDate.setDate(expiryDate.getDate() + (parseInt(plan.expiryDay, 10) || 0));
                expiry = firebase.firestore.Timestamp.fromDate(expiryDate);
            }

            tx.set(database.collection('wallet').doc(walletId), {
                'id': walletId,
                'amount': price,
                'date': firebase.firestore.FieldValue.serverTimestamp(),
                'isTopUp': false,
                'note': 'Store subscription purchase',
                'payment_method': 'Wallet',
                'payment_status': 'success',
                'transactionUser': 'user',
                'user_id': cuser_id
            });

            tx.set(database.collection('vendor_subscriptions').doc(subscriptionId), {
                'id': subscriptionId,
                'planId': plan.id,
                'vendorID': plan.vendorID,
                'customerId': cuser_id,
                /* A snapshot, not a reference: a store renaming or deleting a
                 * plan must not change what an existing subscriber is shown.
                 * The store panel reads subscription.plan.title for exactly
                 * this reason. */
                'plan': plan,
                'startDate': firebase.firestore.Timestamp.fromDate(new Date()),
                'expiryDate': expiry,
                'status': 'active',
                'createdAt': firebase.firestore.FieldValue.serverTimestamp()
            });

            tx.set(database.collection('vendor_subscription_payments').doc(paymentId), {
                'id': paymentId,
                'planId': plan.id,
                'vendorID': plan.vendorID,
                'customerId': cuser_id,
                'amount': price,
                /* Recorded on the payment, never re-derived: the platform
                 * changing its cut must not rewrite what an older payment
                 * earned. */
                'adminCommission': commission,
                'vendorEarning': price - commission,
                'payment_method': 'Wallet',
                'createdAt': firebase.firestore.FieldValue.serverTimestamp()
            });
        });

        /* After the transaction, never inside it: the money is committed and
         * an email that fails must not roll anything back or look like a
         * failed purchase. */
        var expiryDate = null;
        if (String(plan.expiryDay) !== '-1') {
            expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + (parseInt(plan.expiryDay, 10) || 0));
        }

        await sendSubscriptionMail(await storeSubscriptionMailDetails(plan, vendor, expiryDate));
    }

    /* Finishes a purchase the customer started before topping up.
     *
     * The plan is re-read rather than taken from the saved intent, so the
     * charge is always the price as it stands now. If it has changed since
     * they set out, NOTHING is bought - the money is sitting in their wallet
     * and they can decide for themselves. Charging a price they never agreed
     * to is worse than making them press the button again.
     *
     * Returns a short result the calling screen can report. */
    async function completePendingStoreSubscription() {
        var intent = readStoreSubscriptionIntent();

        if (!intent || cuser_id == '') {
            return null;
        }

        try {
            var planDoc = await database.collection('vendor_subscription_plans')
                .doc(intent.planId).get();

            if (!planDoc.exists) {
                clearStoreSubscriptionIntent();
                return { status: 'gone' };
            }

            var plan = planDoc.data();
            plan.id = plan.id || planDoc.id;

            if (plan.isEnable !== true) {
                clearStoreSubscriptionIntent();
                return { status: 'gone' };
            }

            var price = parseFloat(plan.price || 0) || 0;
            if (price !== intent.price) {
                clearStoreSubscriptionIntent();
                return { status: 'price_changed', plan: plan };
            }

            var vendor = null;
            var vendorDoc = await database.collection('vendors').doc(plan.vendorID).get();
            if (vendorDoc.exists) {
                vendor = vendorDoc.data();
            }

            await purchaseStorePlanFromWallet(plan, vendor);
            clearStoreSubscriptionIntent();

            return { status: 'bought', plan: plan };
        } catch (err) {
            /* Left in place on a failure that is not about money, so the
             * customer can try again. An insufficient balance means the
             * top-up did not cover it, which is not going to fix itself. */
            if (err && err.message === 'INSUFFICIENT') {
                clearStoreSubscriptionIntent();
                return { status: 'insufficient', plan: null };
            }

            console.error('pending subscription could not be completed', err);
            return { status: 'failed' };
        }
    }

    /* ---- May this customer see wholesale at all? -------------------------
     *
     * Settled with the client on 30 September: WHOLESALE IS FOR APPROVED
     * BUSINESS ACCOUNTS. A customer without one sees the shop without it -
     * no bulk prices, no tier ladder, and no wholesale-only products.
     *
     * "Approved" is the admin panel's decision, not the customer's request:
     * accountType "business" AND businessProfile.status "approved"
     * (APP-SPEC-ADMIN.md 18). A pending or refused application buys nothing.
     *
     * FAILING CLOSED IS DELIBERATE. A read that errors, or a visitor who is
     * signed out, gets RETAIL. Withholding a discount from someone entitled
     * to it is a support call; handing it to everyone when Firestore hiccups
     * is the client's margin.
     *
     * !! THIS IS A BUSINESS RULE, NOT A SECURITY BOUNDARY. This panel posts
     * prices from the browser to the cart controller, so the server re-checks
     * the same rule in ProductController. And until Firestore rules land, a
     * customer can still write businessProfile.status themselves - see
     * APP-SPEC-WEB.md 19.
     * -------------------------------------------------------------------- */


    /* ---- Does THIS product need a business account? ----------------------
     *
     * Two rules exist and they sit at different levels:
     *
     *   the platform's     wholesale is for approved business accounts,
     *   (client, 30 Sep)   on every product, full stop
     *
     *   the product's      `wholesaleBusinessOnly`, written by the store
     *   (store app/panel)  panel and the store app for ONE product
     *
     * They are combined with OR, so the product flag can only ever TIGHTEN
     * the rule, never loosen it. That matters because the flag defaults to
     * FALSE: reading it on its own would open every existing product - none
     * of which has ever had it set - to ordinary customers, which is the
     * exact reversal of the 30 September decision.
     *
     * While WHOLESALE_IS_BUSINESS_ONLY_PLATFORM_WIDE is true the product flag
     * changes nothing, because the platform rule already covers everything.
     * It is wired up anyway so that IF the client ever moves to per-product
     * control, flipping that one constant is the whole code change.
     *
     * !! FLIPPING IT IS NOT SAFE ON ITS OWN. With it false, a product whose
     * flag is absent OR false is open to every customer - and the store panel
     * writes false by default, so that is most of the catalogue. There is no
     * way to tell "the vendor chose open" from "nobody ever set it". Anyone
     * flipping this must set wholesaleBusinessOnly on the existing products
     * first, as a deliberate data decision, and that is the client's call.
     * -------------------------------------------------------------------- */

    var WHOLESALE_IS_BUSINESS_ONLY_PLATFORM_WIDE = true;

    function productNeedsBusinessAccount(product) {
        return WHOLESALE_IS_BUSINESS_ONLY_PLATFORM_WIDE
            || (product && product.wholesaleBusinessOnly === true);
    }

    /* May this customer have wholesale prices on this product? Approved
     * accounts always may; anyone else may only where the product does not
     * require an account at all. */
    function mayBuyWholesaleOf(product) {
        return customerMayBuyWholesale || !productNeedsBusinessAccount(product);
    }

    var customerMayBuyWholesale = false;

    async function loadBusinessAccountStatus() {
        if (typeof cuser_id === 'undefined' || cuser_id === '') {
            return false;
        }

        try {
            var snapshot = await database.collection('users').doc(cuser_id).get();

            if (!snapshot.exists) {
                return false;
            }

            var user = snapshot.data() || {};
            var profile = user.businessProfile || {};

            customerMayBuyWholesale = user.accountType === 'business' && profile.status === 'approved';
        } catch (err) {
            console.error('business account status could not be read; wholesale withheld', err);
            customerMayBuyWholesale = false;
        }

        return customerMayBuyWholesale;
    }

    /* Started immediately, awaited once inside processVendorData, the same
     * shape discoveryRegionsReady uses. */
    var businessAccountReady = loadBusinessAccountStatus();

    /* A wholesale-ONLY product this customer may not buy.
     *
     * READS THE RAW PRODUCT, not the computed price object, on purpose:
     * fetchVendorPriceData() returns {} outright when adminCommissionSettings
     * is missing from localStorage - which happens whenever the section cookie
     * points at a section that no longer exists. Keying this on the price
     * object would then hide nothing and leak every wholesale-only product.
     * saleType and wholesaleEnabled are on the product document itself, so
     * this holds whatever state the pricing is in.
     *
     * Listings FILTER THE ARRAY before drawing rather than skipping inside the
     * loop, so "everything was hidden" falls through to the screen's own empty
     * message instead of leaving a blank row - and so the row-opening
     * count == 1 logic several screens use still fires on the first card
     * actually drawn. */
    function hiddenWholesaleOnlyProduct(product) {
        return !!(product
            && product.wholesaleEnabled === true
            && String(product.saleType || '').toLowerCase() === 'wholesale'
            && !mayBuyWholesaleOf(product));
    }

    /* Drops the products this customer may not see. Screens await
     * businessAccountReady before calling it. */
    function withoutHiddenWholesale(products) {
        return Array.isArray(products)
            ? products.filter(function (product) { return !hiddenWholesaleOnlyProduct(product); })
            : products;
    }

    async function fetchVendorPriceData() {
        let priceData = {}; // To store price data for each vendor
        let adminCommissionSettings = localStorage.getItem('adminCommissionSettings');
        // Check if admin commission settings exist
        if (adminCommissionSettings && adminCommissionSettings !== undefined) {
            adminCommissionSettings = JSON.parse(adminCommissionSettings);
            // Fetch all vendors in parallel
            const vendorSnapshot = await database.collection('vendors').get();
            const vendorCommissions = {};
            vendorSnapshot.docs.forEach(doc => {
                vendorCommissions[doc.id] = doc.data().adminCommission || adminCommissionSettings;
            });
            const productSnapshot = await database.collection('vendor_products').get();
            const promises = productSnapshot.docs.map(doc => {
                const productData = doc.data();
                const vendorID = productData.vendorID;
                // Fetch the corresponding vendor commission
                const commissionData = vendorCommissions[vendorID] || adminCommissionSettings;
                return processVendorData(productData, commissionData);
            });
            // Wait for all promises to resolve
            const results = await Promise.all(promises);
            results.forEach(result => {
                priceData[result.productId] = result.finalPrice;
            });
        }
        return priceData;
    }
    // Process each vendor's data and calculate the price with admin commission
    async function processVendorData(productData, commissionData) {
        let final_price = parseFloat(productData.price); // Default to the base price
        let adminCommissionSettings = localStorage.getItem('adminCommissionSettings');
        if (adminCommissionSettings && adminCommissionSettings !== undefined) {
            adminCommissionSettings = JSON.parse(adminCommissionSettings);
        }
        // Handle the commission logic (if any)
        if (commissionData && adminCommissionSettings.enable) {
            if (commissionData.type === "percentage") {
                price = parseFloat(productData.price) + (parseFloat(productData.price) * parseFloat(commissionData
                    .commission) / 100);
            } else {
                price = parseFloat(productData.price) + parseFloat(commissionData.commission);
            }
        } else {
            price = parseFloat(productData.price);
        }
        final_price = {
            price: price
        };
        // Check for discount price (disPrice)
        if (productData.disPrice && productData.disPrice !== '0' && productData.disPrice !== "") {
            if (commissionData && adminCommissionSettings.enable) {
                if (commissionData.type === "percentage") {
                    dis_price = parseFloat(productData.disPrice) + (parseFloat(productData.disPrice) * parseFloat(
                            commissionData.commission) /
                        100);
                } else {
                    dis_price = parseFloat(productData.disPrice) + parseFloat(commissionData.commission);
                }
                final_price = {
                    price: price,
                    dis_price: dis_price
                };
            } else {
                final_price = {
                    price: parseFloat(productData.price),
                    dis_price: parseFloat(productData.disPrice)
                };
            }
        }
        // Check for variant prices if available
        if (productData.item_attribute && productData.item_attribute.variants?.length > 0) {
            let variantPrices = productData.item_attribute.variants.map(v => ({
                variant_id: v.variant_id,
                variant_price: v.variant_price
            }));
            let minPrice = Math.min(...variantPrices.map(v => v.variant_price));
            let maxPrice = Math.max(...variantPrices.map(v => v.variant_price));
            if (commissionData && adminCommissionSettings.enable) {
                if (commissionData.type === "percentage") {
                    minPrice = minPrice + (minPrice * parseFloat(commissionData.commission) / 100);
                    maxPrice = maxPrice + (maxPrice * parseFloat(commissionData.commission) / 100);
                } else {
                    minPrice = minPrice + parseFloat(commissionData.commission);
                    maxPrice = maxPrice + parseFloat(commissionData.commission);
                }
            }
            // If variants have a range, use that
            if (minPrice !== maxPrice) {
                final_price = {
                    min: minPrice,
                    max: maxPrice,
                    variants: Object.fromEntries(variantPrices.map(v => [
                        v.variant_id,
                        commissionData && adminCommissionSettings.enable ?
                        (commissionData.type === "percentage" ?
                            parseFloat(v.variant_price) + (parseFloat(v.variant_price) * parseFloat(commissionData.commission) / 100) :
                            parseFloat(v.variant_price) + parseFloat(commissionData.commission)) :
                        parseFloat(v.variant_price)
                    ]))
                };
            } else {
                final_price = {
                    max: minPrice,
                    variants: Object.fromEntries(variantPrices.map(v => [
                        v.variant_id,
                        commissionData && adminCommissionSettings.enable ?
                        (commissionData.type === "percentage" ?
                            parseFloat(v.variant_price) + (parseFloat(v.variant_price) * parseFloat(commissionData.commission) / 100) :
                            parseFloat(v.variant_price) + parseFloat(commissionData.commission)) :
                        parseFloat(v.variant_price)
                    ]))
                };
            }
        }
        /* The wholesale tier, carried on the same object as every other
         * price so each screen gets it from the one lookup it already
         * does.
         *
         * It goes through the SAME admin commission as the retail price.
         * Without that a bulk line would quietly skip the platform's cut -
         * and the bigger the order, the more it would skip.
         *
         * Added after the branches above, not inside them, because
         * final_price is reassigned wholesale in several of them. */
        await businessAccountReady;

        /* THE SINGLE SWITCH. Everything downstream - the badge, the tier
         * ladder, the minimum quantity, the price actually charged - is
         * already gated on wholesaleEnabled, so withholding it here withholds
         * wholesale everywhere at once rather than in seven screens. */
        final_price.wholesaleEnabled = productData.wholesaleEnabled === true
            && mayBuyWholesaleOf(productData);
        final_price.wholesaleMinQty = parseInt(productData.wholesaleMinQty || 0) || 0;

        /* The same verdict as hiddenWholesaleOnlyProduct(), carried on the
         * price object for the ONE screen that cannot use that function:
         * favourites renders a placeholder card per favourite record and fills
         * it in afterwards, so the product document is not in hand at the
         * moment the card is drawn. Everywhere else prefers the raw product,
         * which survives an empty priceData. */
        final_price.wholesaleOnlyHidden = productData.wholesaleEnabled === true
            && String(productData.saleType || '').toLowerCase() === 'wholesale'
            && !mayBuyWholesaleOf(productData);

        /* Carried so the detail page can post it: the server re-checks the
         * same rule and cannot read the product document itself. */
        final_price.wholesaleBusinessOnly = productData.wholesaleBusinessOnly === true;


        /* The store's three-way choice: "retail", "wholesale" (sold ONLY in
         * wholesale quantities) or "both". Anything else, including a product
         * saved before the field existed, is "both" - which is how this panel
         * behaved before the field arrived, so older products are untouched. */
        final_price.saleType = ['retail', 'wholesale', 'both']
            .indexOf(String(productData.saleType || '').toLowerCase()) !== -1
                ? String(productData.saleType).toLowerCase()
                : 'both';

        if (final_price.wholesaleEnabled) {
            var withCommission = function (value) {
                var amount = parseFloat(value);
                if (isNaN(amount)) {
                    return null;
                }
                if (commissionData && adminCommissionSettings.enable) {
                    return commissionData.type === "percentage"
                        ? amount + (amount * parseFloat(commissionData.commission) / 100)
                        : amount + parseFloat(commissionData.commission);
                }
                return amount;
            };

            final_price.wholesale_price = withCommission(productData.wholesalePrice);

            /* The price tiers, every one of them carrying the commission.
             *
             * A store can set up to five - "from 15 units 3,500 each, from
             * 100 units 2,500 each" - and the customer is charged the
             * highest tier their quantity reaches. Sorted smallest first so
             * every screen can walk the list the same way.
             *
             * A product saved before tiers existed has no list, so the older
             * price and minimum quantity stand in as the single tier they
             * are. The store panel writes both shapes, so the two normally
             * agree and this only matters for older products. */
            final_price.wholesale_tiers = (Array.isArray(productData.wholesaleTiers)
                    ? productData.wholesaleTiers : [])
                .map(function (tier) {
                    return {
                        minQty: parseInt(tier && tier.minQty) || 0,
                        price: withCommission(tier && tier.price)
                    };
                })
                .filter(function (tier) {
                    return tier.minQty > 0 && tier.price !== null && !isNaN(tier.price);
                })
                .sort(function (a, b) { return a.minQty - b.minQty; });

            if (final_price.wholesale_tiers.length === 0
                && final_price.wholesaleMinQty > 0
                && final_price.wholesale_price !== null
                && !isNaN(final_price.wholesale_price)) {
                final_price.wholesale_tiers = [{
                    minQty: final_price.wholesaleMinQty,
                    price: final_price.wholesale_price
                }];
            }

            /* The entry tier is what a badge advertises and what the older
             * two fields mean, so they are lined up with the list rather
             * than left as whatever was stored separately. */
            if (final_price.wholesale_tiers.length > 0) {
                final_price.wholesaleMinQty = final_price.wholesale_tiers[0].minQty;
                final_price.wholesale_price = final_price.wholesale_tiers[0].price;
            }

            /* A variant's own wholesale price wins over the product's, the
             * same way the store panel resolves it. A variant without one
             * falls back to the product's. */
            if (productData.item_attribute && productData.item_attribute.variants?.length > 0) {
                final_price.wholesale_variants = Object.fromEntries(
                    productData.item_attribute.variants.map(function (v) {
                        var own = v.variant_wholesale_price;
                        var use = (own !== undefined && own !== null && own !== '')
                            ? own
                            : productData.wholesalePrice;
                        return [v.variant_id, withCommission(use)];
                    })
                );
            }
        }

        return {
            productId: productData.id,
            finalPrice: final_price
        };
    }

    /* ---- A VARIANT'S LADDER ------------------------------------------------
     *
     * A variant carries ONE wholesale figure. The question is what it means.
     *
     * Read as "this variant's only wholesale price" it DESTROYS the ladder: a
     * product with tiers at 10/50/150 charges the same per piece at 150 as at
     * 10, which is not a tiered product at all. That is what a store sees when
     * it adds sizes to a tiered product.
     *
     * So it is read as THIS VARIANT'S TIER-ONE PRICE. The product's tiers own
     * the quantity breaks and the steps between them; the variant shifts the
     * whole ladder to start at its own figure.
     *
     *     product   10 -> 949   50 -> 849   150 -> 749
     *     variant entry 999  =>  999, 899, 799     (+50 throughout)
     *     variant entry 949  =>  949, 849, 749     (unchanged)
     *     variant blank      =>  the product's ladder as it stands
     *
     * One number per variant, which is all the panel and the app offer, and
     * the ladder survives. A variant that happens to carry the product's own
     * tier-one price - which is what a store enters when the sizes cost the
     * same - comes out identical to the product, so existing data starts
     * working without being re-entered.
     *
     * Steps are kept as DIFFERENCES, not ratios: a store setting 949/849/749
     * means "a hundred rupees a step", and that is what it should stay when a
     * size costs fifty more. A tier that would fall to zero or below is
     * dropped rather than clamped - a ladder that deep says the figures are
     * wrong, and silently inventing a price would hide it.
     * ---------------------------------------------------------------------- */
    function variantWholesaleTiers(tiers, variantEntryPrice) {
        if (!Array.isArray(tiers) || tiers.length === 0) {
            return [];
        }

        var entry = parseFloat(variantEntryPrice);
        var base = parseFloat(tiers[0].price);

        /* No figure of its own, or an unusable one: the product's ladder
         * applies to this variant unchanged. */
        if (isNaN(entry) || entry <= 0 || isNaN(base)) {
            return tiers.slice();
        }

        return tiers
            .map(function (tier) {
                return {
                    minQty: tier.minQty,
                    price: entry + (parseFloat(tier.price) - base)
                };
            })
            .filter(function (tier) {
                return !isNaN(tier.price) && tier.price > 0;
            });
    }

    /* The tier a given quantity has reached, or null for none.
     *
     * The HIGHEST tier that the quantity meets wins, so the list is walked
     * forwards - it is sorted smallest first - and the last match kept.
     *
     * A tier that is not actually cheaper than the price the customer would
     * otherwise pay is skipped, which is the same test the cart applies on the
     * server. Pass the retail price to have that checked; leave it out and
     * every tier is considered.
     *
     * This must agree with applyWholesalePrice() in ProductController, or the
     * page promises one price and the cart charges another. */
    function wholesaleTierFor(tiers, quantity, retailPrice) {
        if (!Array.isArray(tiers) || tiers.length === 0) {
            return null;
        }

        var qty = parseInt(quantity) || 0;
        var retail = parseFloat(retailPrice);
        var applied = null;

        tiers.forEach(function (tier) {
            if (!tier || !(tier.minQty > 0) || tier.price === null || isNaN(tier.price)) {
                return;
            }
            if (qty < tier.minQty) {
                return;
            }
            if (!isNaN(retail) && !(parseFloat(tier.price) < retail)) {
                return;
            }
            applied = tier;
        });

        return applied;
    }

    /* The next tier up from where the customer is now, or null when they are
     * already on the deepest one. Used to tell them what one more step buys. */
    function nextWholesaleTier(tiers, quantity) {
        if (!Array.isArray(tiers) || tiers.length === 0) {
            return null;
        }

        var qty = parseInt(quantity) || 0;

        for (var i = 0; i < tiers.length; i++) {
            if (tiers[i] && tiers[i].minQty > qty) {
                return tiers[i];
            }
        }

        return null;
    }

    /* The wholesale tier as a badge, from the price object fetchVendorPriceData()
     * already returns - so a listing shows the bulk price without a second read.
     * A product with variants shows the CHEAPEST tier, matching how those cards
     * already show a price range. Returns '' when the product has no tier. */
    /* The smallest quantity a product may be bought in. Only a
     * wholesale-only product has one, and it is the ENTRY tier - the cheapest
     * quantity that unlocks a wholesale price, not the deepest. A store
     * selling in tens with a better price at fifty still sells tens. */
    /* The headline price for a product that is sold ONLY in wholesale
     * quantities, or '' when the ordinary retail price is the honest one.
     *
     * A wholesale-only product cannot be bought singly - the quantity box
     * opens at the entry tier - so showing its retail price at the top of the
     * page advertises a figure no customer can ever pay. The kurti read
     * "Rs. 1,509" while the cheapest real purchase was ten pieces at Rs. 959.
     *
     * The entry TIER is what a buyer actually pays first, so that is what is
     * shown. With variants it is a range, because each size starts its own
     * ladder - the same reason the retail headline is a range. */
    function wholesaleHeadlinePrice(finalPrice) {
        if (!finalPrice
            || !finalPrice.wholesaleEnabled
            || finalPrice.saleType !== 'wholesale') {
            return '';
        }

        var amounts = [];

        if (finalPrice.wholesale_variants) {
            amounts = Object.values(finalPrice.wholesale_variants)
                .map(function (v) { return parseFloat(v); })
                .filter(function (v) { return !isNaN(v) && v > 0; });
        } else if (Array.isArray(finalPrice.wholesale_tiers) && finalPrice.wholesale_tiers.length > 0) {
            amounts = [parseFloat(finalPrice.wholesale_tiers[0].price)];
        } else if (finalPrice.wholesale_price !== null && finalPrice.wholesale_price !== undefined) {
            amounts = [parseFloat(finalPrice.wholesale_price)];
        }

        amounts = amounts.filter(function (v) { return !isNaN(v) && v > 0; });

        if (amounts.length === 0) {
            return '';
        }

        var low = Math.min.apply(null, amounts);
        var high = Math.max.apply(null, amounts);

        return low === high
            ? getProductFormattedPrice(low)
            : getProductFormattedPrice(low) + ' - ' + getProductFormattedPrice(high);
    }

    function minimumOrderQuantity(finalPrice) {
        if (!finalPrice || finalPrice.saleType !== 'wholesale' || !finalPrice.wholesaleEnabled) {
            return 1;
        }

        var entry = (Array.isArray(finalPrice.wholesale_tiers) && finalPrice.wholesale_tiers.length > 0)
            ? parseInt(finalPrice.wholesale_tiers[0].minQty) || 0
            : parseInt(finalPrice.wholesaleMinQty) || 0;

        return entry > 1 ? entry : 1;
    }

    function wholesaleBadgeHtml(finalPrice) {
        if (!finalPrice || !finalPrice.wholesaleEnabled || !(finalPrice.wholesaleMinQty > 0)) {
            return '';
        }

        var amount = null;
        if (finalPrice.wholesale_variants) {
            var tiers = Object.values(finalPrice.wholesale_variants)
                .filter(function (v) { return v !== null && !isNaN(v); });
            if (tiers.length > 0) {
                amount = Math.min.apply(null, tiers);
            }
        } else if (finalPrice.wholesale_price !== null && finalPrice.wholesale_price !== undefined) {
            amount = finalPrice.wholesale_price;
        }

        if (amount === null || isNaN(amount)) {
            return '';
        }

        var html = '<span class="badge badge-info">' +
            "{{ trans('lang.wholesale') }}" + ' ' + getProductFormattedPrice(parseFloat(amount)) + ' ' +
            "{{ trans('lang.wholesale_from_units') }}".replace(':count', finalPrice.wholesaleMinQty) +
            '</span>';

        /* Sold in packs - worth knowing from the listing, before the customer
         * opens the product and finds the quantity box will not go to one. */
        var minimum = minimumOrderQuantity(finalPrice);
        if (minimum > 1) {
            html += ' <span class="badge badge-dark">' +
                "{{ trans('lang.wholesale_only_minimum') }}".replace(':count', minimum) +
                '</span>';
        }

        return '<div class="pro-wholesale">' + html + '</div>';
    }

    function getProductFormattedPrice(price) {
        if (price != null && price != '' && price != undefined) {
            if (currencyAtRight) {
                return price.toFixed(decimal_degits) + "" + currentCurrency;
            } else {
                return currentCurrency + "" + price.toFixed(decimal_degits);
            }
        } else {
            return currentCurrency + "" + 0;
        }
    }

    function encodeGeohash(latitude, longitude, precision = 9) {
        const BASE32 = "0123456789bcdefghjkmnpqrstuvwxyz";
        let isEven = true;
        let bit = 0, ch = 0, geohash = "";
        let latRange = [-90, 90];
        let lonRange = [-180, 180];
        while (geohash.length < precision) {
            let mid;
            if (isEven) {
                mid = (lonRange[0] + lonRange[1]) / 2;
                if (longitude > mid) { ch |= (1 << (4 - bit)); lonRange[0] = mid; } 
                else { lonRange[1] = mid; }
            } else {
                mid = (latRange[0] + latRange[1]) / 2;
                if (latitude > mid) { ch |= (1 << (4 - bit)); latRange[0] = mid; } 
                else { latRange[1] = mid; }
            }
            isEven = !isEven;
            if (bit < 4) { bit++; } 
            else { geohash += BASE32[ch]; bit = 0; ch = 0; }
        }
        return geohash;
    }

    function checkIfStoreIsOpen(data) {
        var currentdate = new Date();
        var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var currentDay = days[currentdate.getDay()];
        var hour = currentdate.getHours().toString().padStart(2, '0');
        var minute = currentdate.getMinutes().toString().padStart(2, '0');
        var currentTime = hour + ':' + minute;
        if (!data.workingHours) return false;
        if (data.hasOwnProperty('workingHours')) {
            for (let dayData of data.workingHours) {
                if (dayData.day === currentDay) {
                    for (let slot of dayData.timeslot) {
                        if (currentTime >= slot.from && currentTime <= slot.to) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    function getStoreNextOpeningTime(data) {
        const now = new Date();
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const currentDay = days[now.getDay()];
        const currentTime = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
        if (!data.workingHours || data.workingHours.length === 0) {
            return "{{trans('lang.closed')}}";
        }
        const todayData = data.workingHours.find(d => d.day === currentDay);
        if (todayData && todayData.timeslot.length) {
            const futureToday = todayData.timeslot
                .filter(t => t.from > currentTime)
                .sort((a, b) => a.from.localeCompare(b.from));

            if (futureToday.length) {
                return `{{trans('lang.next_available_today_at')}} ${getTimeFormat(futureToday[0].from)}`;
            }
        }
        const currentIndex = days.indexOf(currentDay);
        for (let i = 1; i <= 7; i++) {
            const nextDayIndex = (currentIndex + i) % 7;
            const nextDay = days[nextDayIndex];
            const dayData = data.workingHours.find(d => d.day === nextDay);

            if (dayData && dayData.timeslot.length) {
                const nextSlot = dayData.timeslot.sort((a, b) => a.from.localeCompare(b.from))[0];
                if (i === 1) {
                    return `{{trans('lang.next_available_tomorrow_at')}} ${getTimeFormat(nextSlot.from)}`;
                } else {
                    return `{{trans('lang.next_available')}} ${nextDay} {{trans('lang.at')}} ${getTimeFormat(nextSlot.from)}`;
                }
            }
        }
        return "{{trans('lang.closed')}}";
    }

    function getTimeFormat(time) {
        let [h, m] = time.split(":");
        h = parseInt(h);
        return (h % 12 || 12) + ":" + m + (h >= 12 ? " PM" : " AM");
    }

    function formatCurrency(amount, currency = {}) {
        const symbol = currency.symbol || '';
        const decimals = currency.decimal_degits ?? 2;
        const symbolAtRight = Boolean(currency.symbolAtRight);
        const formatted = parseFloat(amount).toFixed(decimals);
        return symbolAtRight
            ? formatted + ' ' + symbol
            : symbol + formatted;
    }

    /* Keeps the `userCountryName` cookie in step with the active address.
     * Every path that writes address_lat/address_lng calls this - the cookie
     * drives the tax lookup in ten views, so a stale value taxes a customer
     * against a country they have left.
     *
     * Also records `userCountryCode`, which region detection needs: the ISO
     * code survives the name differences a reverse lookup throws up, such as
     * Nominatim returning "Cote d'Ivoire" where the region says "Ivory Coast".
     *
     * A failed lookup leaves the existing cookies alone rather than blanking
     * them: a slightly old country beats no country at all. */
    async function setUserCountryCookie(lat, lng) {
        if (!lat || !lng) {
            return '';
        }
        try {
            var country = await lookupCountry(lat, lng);
            if (country.name) {
                setCookie('userCountryName', country.name, 365);
            }
            if (country.code) {
                setCookie('userCountryCode', country.code, 365);
            }
            return country.name;
        } catch (err) {
            return '';
        }
    }

    /* Returns {name, code}. Kept separate from getCountryFromLatLng so that
     * function's contract - a plain country name - stays as it was. */
    async function lookupCountry(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${lat}&lon=${lng}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        return {
            name: data?.address?.country || '',
            code: data?.address?.country_code || ''
        };
    }

    async function getCountryFromLatLng(lat, lng) {
        const country = await lookupCountry(lat, lng);
        return country.name;
    }

    /* ---- Region detection ------------------------------------------------
     * Works out which region the visitor is browsing in.
     * Specified in docs/WEB-SPEC-REGION-DETECTION.md.
     *
     * The answer decides DISCOVERY only - which sections and stores to show,
     * and which currency to render before a store has been chosen. It must
     * never decide a price, a payment method or a delivery charge: those
     * follow the STORE'S own `regionId`. A region is a property of platform
     * data, never of a customer.
     *
     * Resolving to NO region is a correct outcome, not a failure. Every
     * consumer must then behave exactly as the panel does today. That is what
     * keeps this additive - nothing changes until something opts in.
     * -------------------------------------------------------------------- */

    var REGION_COOKIE = 'active_region_id';
    var REGION_SOURCE_COOKIE = 'active_region_source';
    var REGION_CHOICE_COOKIE = 'region_choice';
    var REGION_KEY_COOKIE = 'active_region_key';

    /* Eight regions and three zones: each collection is fetched whole and
     * filtered in memory, so none of this needs a composite index. */
    var regionCache = {
        regions: null,
        zones: null,
        defaultRegionId: undefined
    };

    /* One in-flight resolve shared by concurrent callers on a page load. */
    var regionResolvePromise = null;

    async function loadPublishedRegions() {
        if (regionCache.regions === null) {
            var snapshots = await database.collection('regions').get();
            regionCache.regions = snapshots.docs
                .map(function (doc) {
                    var data = doc.data();
                    data.id = data.id || doc.id;
                    return data;
                })
                .filter(function (region) {
                    return region.publish !== false;
                });
        }
        return regionCache.regions;
    }

    /* Holds every zone, published or not: zoneForPoint() wants only the
     * published ones, but regionIdForStore() has to resolve the zone a store
     * actually sits in either way. */
    async function loadZones() {
        if (regionCache.zones === null) {
            var snapshots = await database.collection('zone').get();
            regionCache.zones = snapshots.docs.map(function (doc) {
                var data = doc.data();
                data.id = data.id || doc.id;
                return data;
            });
        }
        return regionCache.zones;
    }

    /* settings/RegionDefaults is an admin-side setting that does not exist
     * yet. Absent means there is no default, which is a valid state. */
    async function loadDefaultRegionId() {
        if (regionCache.defaultRegionId === undefined) {
            try {
                var snapshot = await database.collection('settings').doc('RegionDefaults').get();
                var data = snapshot.exists ? snapshot.data() : null;
                regionCache.defaultRegionId = (data && data.defaultRegionId) ? data.defaultRegionId : null;
            } catch (err) {
                regionCache.defaultRegionId = null;
            }
        }
        return regionCache.defaultRegionId;
    }

    /* `regionIds` is the truth. `regionId` holds only the first entry and is
     * deprecated - but it is the ONLY thing some zones carry, so it is read
     * when the array is absent, never in preference to it. */
    function zoneRegionIds(zone) {
        if (zone && Array.isArray(zone.regionIds)) {
            return zone.regionIds;
        }
        if (zone && zone.regionId) {
            return [zone.regionId];
        }
        return [];
    }

    /* Deliberately does NOT reuse getUserZoneId(): that function caches into
     * the shared `user_zone_id` global that twenty views filter stores by,
     * returns the previous value when nothing matches, and hands back an id
     * rather than the document whose regionIds are needed here. */
    async function zoneForPoint(lat, lng) {
        var testY = parseFloat(lat);
        var testX = parseFloat(lng);
        if (isNaN(testY) || isNaN(testX)) {
            return null;
        }
        var zones = await loadZones();
        for (var i = 0; i < zones.length; i++) {
            if (zones[i].publish !== true) {
                continue;
            }
            var area = zones[i].area;
            if (!Array.isArray(area) || area.length === 0) {
                continue;
            }
            var verticesX = [];
            var verticesY = [];
            for (var j = 0; j < area.length; j++) {
                verticesX.push(area[j].longitude);
                verticesY.push(area[j].latitude);
            }
            if (is_in_polygon(verticesX, verticesY, testX, testY)) {
                return zones[i];
            }
        }
        return null;
    }

    function normaliseCountry(value) {
        return (value || '').toString().trim().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    /* Matches on countryCode and countryName only. The region's own `code` is
     * deliberately excluded: Gabon carries code 'GB' with countryCode 'GA',
     * so matching on `code` would file a British visitor in Gabon. */
    function regionsMatchingCountry(regions, countryName, countryCode) {
        var name = normaliseCountry(countryName);
        var code = normaliseCountry(countryCode);
        if (!name && !code) {
            return [];
        }
        return regions.filter(function (region) {
            if (code && normaliseCountry(region.countryCode) === code) {
                return true;
            }
            if (name && normaliseCountry(region.countryName) === name) {
                return true;
            }
            return false;
        });
    }

    /* What the resolution depends on. A change here re-resolves; otherwise
     * the cookies are trusted and nothing is read from Firestore. */
    function regionFingerprint() {
        return [
            getCookie(REGION_CHOICE_COOKIE) || '',
            getCookie('address_lat') || '',
            getCookie('address_lng') || '',
            getCookie('userCountryCode') || '',
            getCookie('userCountryName') || ''
        ].join('|');
    }

    async function computeActiveRegion() {
        var regions = await loadPublishedRegions();
        var published = {};
        regions.forEach(function (region) {
            published[region.id] = true;
        });

        /* 1. The customer's own choice always wins and is never silently
         *    overridden by a later address change. */
        var choice = getCookie(REGION_CHOICE_COOKIE);
        if (choice && published[choice]) {
            return { regionId: choice, source: 'explicit', candidates: [] };
        }

        /* 2. The zone, but only when it settles the question. A zone may
         *    serve several regions, and most of them do. */
        var candidates = [];
        var zone = await zoneForPoint(getCookie('address_lat'), getCookie('address_lng'));
        if (zone) {
            var zoneIds = zoneRegionIds(zone).filter(function (id) {
                return published[id];
            });
            if (zoneIds.length === 1) {
                return { regionId: zoneIds[0], source: 'zone', candidates: [] };
            }
            if (zoneIds.length > 1) {
                candidates = zoneIds;
            }
        }

        /* 3. The country, narrowed by whatever the zone offered. */
        var matches = regionsMatchingCountry(
            regions,
            getCookie('userCountryName'),
            getCookie('userCountryCode')
        ).map(function (region) {
            return region.id;
        });

        /* The zone narrows the country matches - but only when it can. A
         * region with no zone at all (Ivory Coast and Senegal each have
         * none) can never appear in a zone's candidate list, so an empty
         * intersection means the zone has nothing useful to say here, not
         * that the country is wrong. Keeping the unnarrowed match stops a
         * wide zone vetoing a precise country answer. */
        if (candidates.length) {
            var narrowed = matches.filter(function (id) {
                return candidates.indexOf(id) !== -1;
            });
            if (narrowed.length) {
                matches = narrowed;
            }
        }
        if (matches.length === 1) {
            return { regionId: matches[0], source: 'country', candidates: [] };
        }

        /* Everything the visitor could plausibly be: several regions in one
         * country, or - when the country matches nothing - whatever the zone
         * offered. With the region picker dropped (spec 7.4) this cannot be
         * settled by asking, so it is only reported. */
        var ambiguousCandidates = [];
        if (matches.length > 1) {
            ambiguousCandidates = matches;
        } else if (candidates.length > 1) {
            ambiguousCandidates = candidates;
        }

        /* 4. The configured default, if the admin panel has one.
         *
         * This has to come BEFORE returning 'ambiguous'. The admin sets a
         * default precisely for visitors who cannot be placed, and returning
         * ambiguous first made the setting unreachable for exactly them. */
        var fallback = await loadDefaultRegionId();
        if (fallback && published[fallback]) {
            return { regionId: fallback, source: 'default', candidates: ambiguousCandidates };
        }

        if (ambiguousCandidates.length) {
            return { regionId: '', source: 'ambiguous', candidates: ambiguousCandidates };
        }

        /* 5. No region. Consumers fall back to today's behaviour. */
        return { regionId: '', source: 'none', candidates: [] };
    }

    /* Resolves and caches. Returns {regionId, source, candidates}.
     *
     * `source` is one of explicit / zone / country / ambiguous / default /
     * none. `candidates` is populated only for 'ambiguous', and is what a
     * region picker should offer the customer. */
    async function resolveActiveRegion(force) {
        var key = regionFingerprint();

        if (!force && getCookie(REGION_KEY_COOKIE) === key) {
            return {
                regionId: getCookie(REGION_COOKIE) || '',
                source: getCookie(REGION_SOURCE_COOKIE) || 'none',
                candidates: []
            };
        }

        if (!force && regionResolvePromise) {
            return regionResolvePromise;
        }

        regionResolvePromise = (async function () {
            try {
                var result = await computeActiveRegion();
                setCookie(REGION_COOKIE, result.regionId, 365);
                setCookie(REGION_SOURCE_COOKIE, result.source, 365);
                setCookie(REGION_KEY_COOKIE, key, 365);
                return result;
            } catch (err) {
                /* A resolver that throws must not take the page with it. */
                return { regionId: '', source: 'none', candidates: [] };
            } finally {
                regionResolvePromise = null;
            }
        })();

        return regionResolvePromise;
    }

    /* The everyday accessor. '' means unresolved - treat it as "no region". */
    async function getActiveRegionId() {
        var result = await resolveActiveRegion();
        return result.regionId;
    }

    /* The region document itself, or null. */
    async function getActiveRegion() {
        var regionId = await getActiveRegionId();
        if (!regionId) {
            return null;
        }
        var regions = await loadPublishedRegions();
        for (var i = 0; i < regions.length; i++) {
            if (regions[i].id === regionId) {
                return regions[i];
            }
        }
        return null;
    }

    /* The write side of the customer's choice, kept here so the cookie
     * contract lives in one place. A picker calls these. */
    async function setRegionChoice(regionId) {
        setCookie(REGION_CHOICE_COOKIE, regionId || '', 365);
        setCookie(REGION_KEY_COOKIE, '', 365);
        regionCurrencyCache = {};
        var chosen = await resolveActiveRegion(true);
        await renderActiveRegionCode();
        return chosen;
    }

    async function clearRegionChoice() {
        deleteCookie(REGION_CHOICE_COOKIE);
        setCookie(REGION_KEY_COOKIE, '', 365);
        regionCurrencyCache = {};
        var cleared = await resolveActiveRegion(true);
        await renderActiveRegionCode();
        return cleared;
    }

    /* ---- The free order-history limit ------------------------------------
 * A customer sees their most recent settings/OrderHistory.freeOrderLimit
 * orders, unless they hold a customer subscription whose plan carries
 * features.fullOrderHistory.
 *
 * Specified in the admin panel's docs/app-spec-customer-subscription.md.
 * -------------------------------------------------------------------- */

    var orderHistorySettingsPromise = null;

    function loadOrderHistorySettings() {
        if (orderHistorySettingsPromise === null) {
            orderHistorySettingsPromise = (async function () {
                try {
                    var snapshot = await database.collection('settings').doc('OrderHistory').get();
                    return snapshot.exists ? snapshot.data() : null;
                } catch (err) {
                    return null;
                }
            })();
        }
        return orderHistorySettingsPromise;
    }

    /* Whether this customer has bought the full history and it has not
     * lapsed. The fields are the same ones a vendor subscription uses. */
    async function hasFullOrderHistory() {
        if (cuser_id == '') {
            return false;
        }
        try {
            var snapshot = await database.collection('users').doc(cuser_id).get();
            var user = snapshot.exists ? snapshot.data() : null;
            var plan = user ? user.subscription_plan : null;
            if (!plan) {
                return false;
            }
            /* A vendor plan sitting on the record never counts, and an
             * absent planFor means vendor. */
            if (plan.planFor !== 'customer') {
                return false;
            }
            var expiry = user.subscriptionExpiryDate;
            if (expiry) {
                var expiryDate = expiry.toDate ? expiry.toDate() : new Date(expiry);
                if (expiryDate < new Date()) {
                    return false;
                }
            }
            return !!(plan.features && plan.features.fullOrderHistory === true);
        } catch (err) {
            /* Fail OPEN. A failed lookup must never hide a customer's own
             * orders from them - that would read as lost data. */
            return true;
        }
    }

    /* How many orders an unsubscribed customer may see. 0 means no limit.
     *
     * A MISSING settings document means the limit is ON at 5, not off. That
     * is what app-spec-region-features.md tells the App developer to do, and
     * both sides have to agree or the same customer sees a different history
     * in the app and on the web. The admin panel writes the document with
     * these same defaults the first time that screen is opened. */
    /* What applies when settings/OrderHistory is missing: the limit is ON,
     * at the number the client settled on (8, 28 Sep 2026) - not off. An
     * absent settings document must not hand every customer a free full
     * history. */
    var ORDER_HISTORY_DEFAULT_LIMIT = 8;

    async function freeOrderHistoryLimit() {
        var settings = await loadOrderHistorySettings();
        if (!settings) {
            return ORDER_HISTORY_DEFAULT_LIMIT;
        }
        if (settings.isLimitEnabled === false) {
            return 0;
        }
        var limit = parseInt(settings.freeOrderLimit, 10);
        return (isNaN(limit) || limit < 0) ? ORDER_HISTORY_DEFAULT_LIMIT : limit;
    }

    /* ---- Currency by region ----------------------------------------------
     * Ported from the admin panel's layouts/app.blade.php.
     *
     * A region may name its own currency; otherwise the globally active
     * currency is used - which is exactly what this panel did before regions
     * existed, so every fallback here reproduces today's behaviour.
     *
     * Three ways in:
     *   getRegionCurrency()        - the region the visitor is browsing in.
     *                                For screens that show money before a
     *                                store has been chosen, such as a
     *                                subscription plan's price.
     *   getCurrencyForRegion(id)   - a named region.
     *   getCurrencyForStore(store) - THE ONE MOST SCREENS WANT. The store's
     *                                own region decides its prices, settled
     *                                22 Sep from the client's documents. A
     *                                French store reads in euros even to a
     *                                visitor browsing from Douala.
     *
     * 52 of this panel's 138 views still read the globally active currency
     * directly. They are converted one at a time, as each is touched for
     * another reason - the *Ref() shims below make that a one-line change.
     * Nothing is converted yet, so nothing changes yet.
     * -------------------------------------------------------------------- */

    var regionCurrencyCache = {};
    var globalCurrencyPromise = null;

    /* The globally active currency: the pre-region behaviour, and the
     * fallback for everything below. Fetched once. */
    async function getGlobalCurrency() {
        if (globalCurrencyPromise === null) {
            globalCurrencyPromise = (async function () {
                try {
                    var snapshot = await database.collection('currencies').where('isActive', '==', true).get();
                    return snapshot.docs.length ? snapshot.docs[0].data() : null;
                } catch (err) {
                    return null;
                }
            })();
        }
        return globalCurrencyPromise;
    }

    async function getCurrencyForRegion(regionId) {
        var key = regionId ? regionId : '__global__';
        if (regionCurrencyCache[key] !== undefined) {
            return regionCurrencyCache[key];
        }

        var currency = null;

        if (regionId) {
            try {
                var regionSnapshot = await database.collection('regions').doc(regionId).get();
                var region = regionSnapshot.exists ? regionSnapshot.data() : null;
                if (region && region.currencyId) {
                    var snapshot = await database.collection('currencies').doc(region.currencyId).get();
                    if (snapshot.exists) {
                        currency = snapshot.data();
                    }
                }
            } catch (err) {
                currency = null;
            }
        }

        if (currency === null) {
            currency = await getGlobalCurrency();
        }

        regionCurrencyCache[key] = currency;
        return currency;
    }

    async function getRegionCurrency() {
        return getCurrencyForRegion(await getActiveRegionId());
    }

    /* A store's region, worked out the way the admin work requires: read the
     * record's own regionId, and fall back to its zone ONLY when that zone
     * serves exactly one region. A zone serving several cannot say which
     * region a store is in, so it is not consulted. */
    async function regionIdForStore(store) {
        if (!store) {
            return '';
        }
        if (store.regionId) {
            return store.regionId;
        }
        if (!store.zoneId) {
            return '';
        }
        var zones = await loadZones();
        for (var i = 0; i < zones.length; i++) {
            if (zones[i].id !== store.zoneId) {
                continue;
            }
            var ids = zoneRegionIds(zones[i]);
            return ids.length === 1 ? ids[0] : '';
        }
        return '';
    }

    async function getCurrencyForStore(store) {
        return getCurrencyForRegion(await regionIdForStore(store));
    }

    /* Shims shaped like the Firestore query every view already used, so
     * converting one was a single line: the old
     * collection(currencies).where(isActive, true) declaration became a
     * call to one of these, and the .get().then() underneath kept working
     * untouched. */
    function currencyRefFor(resolve) {
        return {
            where: function () { return this; },
            limit: function () { return this; },
            orderBy: function () { return this; },
            get: async function () {
                var currency = await resolve();
                if (!currency) {
                    return { docs: [], empty: true, size: 0 };
                }
                return {
                    docs: [{
                        id: currency.id,
                        data: function () { return currency; }
                    }],
                    empty: false,
                    size: 1
                };
            }
        };
    }

    function regionCurrencyRef() {
        return currencyRefFor(function () {
            return getRegionCurrency();
        });
    }

    function recordCurrencyRef(regionId) {
        return currencyRefFor(async function () {
            return getCurrencyForRegion(regionId || await getActiveRegionId());
        });
    }

    function storeCurrencyRef(store) {
        return currencyRefFor(function () {
            return getCurrencyForStore(store);
        });
    }

    /* ---- Region scoping for discovery ------------------------------------
     * Which stores, items and sections a visitor is shown.
     *
     * Prices, payment methods and delivery charges are NOT scoped here: those
     * follow the STORE'S own region. See docs/WEB-SPEC-REGION-DETECTION.md §1.
     *
     * Resolving to no region means "show everything", which is what the panel
     * did before regions existed - so an unresolved visitor sees exactly
     * today's site rather than an empty one.
     * -------------------------------------------------------------------- */

    /* The active region, plus any published sibling sharing its country.
     *
     * DECIDED by the client, 23 Sep 2026: regions that share a countryCode
     * are siblings, and a visitor in one of them sees all of them. This is
     * the intended behaviour, not a placeholder - do not 'fix' it into a
     * strict region-id match.
     *
     * It is load-bearing on the current data. The Yaounde region holds NO
     * stores at all - every store around a Yaounde visitor is filed under
     * Cameroon - so matching on the region id alone would show them an
     * empty site.
     *
     * The consequence to keep in mind: adding a second region inside an
     * existing country makes each of them show the other's stores. If that
     * is ever unwanted, the fix is a real parent field on the region, not a
     * change here. */
    var discoveryRegionIds = [];

    async function loadDiscoveryRegionIds() {
        try {
            var regionId = await getActiveRegionId();
            if (!regionId) {
                discoveryRegionIds = [];
                return discoveryRegionIds;
            }

            var regions = await loadPublishedRegions();
            var active = null;
            for (var i = 0; i < regions.length; i++) {
                if (regions[i].id === regionId) {
                    active = regions[i];
                    break;
                }
            }
            if (!active) {
                discoveryRegionIds = [];
                return discoveryRegionIds;
            }

            var country = normaliseCountry(active.countryCode);
            discoveryRegionIds = regions.filter(function (region) {
                if (region.id === regionId) {
                    return true;
                }
                return country && normaliseCountry(region.countryCode) === country;
            }).map(function (region) {
                return region.id;
            });
            return discoveryRegionIds;
        } catch (err) {
            /* Never let region scoping empty the site. */
            discoveryRegionIds = [];
            return discoveryRegionIds;
        }
    }

    /* Started immediately so the render loops can await it once. */
    var discoveryRegionsReady = loadDiscoveryRegionIds();

    /* Both tests are synchronous, so they drop straight into the existing
     * render loops beside the subscription-validity check. An empty id list
     * means "no region resolved" and lets everything through. */
    function storeMatchesRegion(store) {
        if (!discoveryRegionIds || discoveryRegionIds.length === 0) {
            return true;
        }
        if (!store || !store.regionId) {
            /* A store carrying no region is not hidden - that would make this
             * change subtractive for data nobody has backfilled. */
            return true;
        }
        return discoveryRegionIds.indexOf(store.regionId) !== -1;
    }

    /* On Demand services carry no region of their own - the PROVIDER does, on
     * their `users` record. The map is loaded once so the render loops can
     * stay synchronous, the same way stores are tested.
     *
     * Loaded lazily: a store page has no reason to fetch the provider list. */
    var providerRegionById = null;
    var providerRegionsPromise = null;

    function ensureProviderRegions() {
        if (providerRegionsPromise === null) {
            providerRegionsPromise = (async function () {
                var map = {};
                try {
                    var snapshot = await database.collection('users').where('role', '==', 'provider').get();
                    snapshot.docs.forEach(function (doc) {
                        var data = doc.data();
                        map[data.id || doc.id] = data.regionId || '';
                    });
                } catch (err) {
                    /* Leave the map empty: everything passes. */
                }
                providerRegionById = map;
                return map;
            })();
        }
        return providerRegionsPromise;
    }

    function providerMatchesRegion(providerId) {
        if (!discoveryRegionIds || discoveryRegionIds.length === 0) {
            return true;
        }
        if (!providerId || !providerRegionById) {
            return true;
        }
        var regionId = providerRegionById[providerId];
        if (!regionId) {
            /* A provider carrying no region is not hidden. */
            return true;
        }
        return discoveryRegionIds.indexOf(regionId) !== -1;
    }

    /* Sections carry `regionIds`, an array. Absent OR empty both mean "all
     * regions": 7 of the 8 live sections are in one of those two states, and
     * reading an empty array as "no regions" would blank the landing screen. */
    function sectionMatchesRegion(section) {
        if (!discoveryRegionIds || discoveryRegionIds.length === 0) {
            return true;
        }
        if (!section) {
            return true;
        }
        var ids = section.regionIds;
        if (!Array.isArray(ids) || ids.length === 0) {
            return true;
        }
        for (var i = 0; i < ids.length; i++) {
            if (discoveryRegionIds.indexOf(ids[i]) !== -1) {
                return true;
            }
        }
        return false;
    }

    /* ---- The region badge in the header ----------------------------------
     * Reports the region §5 resolved, beside the location box. It is a
     * read-only indicator, not a control - changing region is the picker,
     * which does not exist yet.
     *
     * When the region is unresolved - no signal, or a country that matches
     * several regions - it shows NOTHING. A wrong code is worse than no
     * code, and an empty badge is the honest rendering of "we do not know
     * yet".
     * -------------------------------------------------------------------- */
    async function renderActiveRegionCode() {
        var badges = $('.active-region-code');
        if (!badges.length) {
            return;
        }
        try {
            var region = await getActiveRegion();
            /* The region's own `code` is what the admin set for it - 'IN',
             * 'YDE', 'CM-DLA'. countryCode is only a fallback for a region
             * saved before `code` was required. */
            var code = region ? (region.code || region.countryCode || '') : '';
            if (!code) {
                badges.hide().text('').removeAttr('title');
                return;
            }
            badges.text(code)
                .attr('title', "{{ trans('lang.your_region') }}: " + (region.name || code))
                .css('display', 'inline-block');
        } catch (err) {
            badges.hide().text('').removeAttr('title');
        }
    }

    $(function () {
        renderActiveRegionCode();
    });

    /* Fills the page-level currency globals declared near the top of this
     * file. It sits here, at the end of the last script block, because
     * regionCurrencyRef() is declared in this block and hoisting does not
     * cross script blocks.
     *
     * Every view that shows money declares its own `refCurrency` and writes
     * these same globals, so all of them must read from one source or the
     * displayed currency becomes a race between two fetches. That is why
     * they were all converted together. */
    var refCurrency = regionCurrencyRef();
    refCurrency.get().then(async function (snapshots) {
        if (!snapshots.docs.length) {
            return;
        }
        currencyData = snapshots.docs[0].data();
        currentCurrency = currencyData.symbol;
        currencyAtRight = currencyData.symbolAtRight;
        if (currencyData.decimal_degits) {
            decimal_degits = currencyData.decimal_degits;
        }
    });
    


        /* ---- Addresses: never print the word "null" ----------------------
         *
         * Bug report 02 point 18: *"123 Yaounde St, null, Tsinga"*.
         *
         * There are TWO sources of that word and this handles both.
         *
         * 1. THE PANELS PRODUCE IT. Every address join here is guarded with
         *    `hasOwnProperty('address')`, which is TRUE when the field exists
         *    and holds null - so `'' + order.address.address` appends the
         *    string "null". Live on 2 Oct: `address` is null on 56 of 123
         *    orders and `landmark` on 66, so this is the common case by far.
         *
         * 2. IT IS BAKED INTO THE STORED TEXT. `locality` arrives from the
         *    phone already joined, with "null" where the geocoder had no
         *    component: "18, null, Yaounde, Region du Centre, null, Cameroun".
         *    27 orders carry that exact string. NO GUARD CAN FIX THOSE - the
         *    word is inside the value - so the segments are dropped here
         *    instead, which repairs the history on screen without a migration.
         *
         * Keeping both in one place matters: there are 25 of these joins
         * across the three panels, and they had all drifted apart.
         * ------------------------------------------------------------------ */

        /* One address component, cleaned. Splits on commas because the value
         * is often itself a joined string (see 2 above). */
        function spideliCleanAddressPart(value) {
            if (value === null || value === undefined) {
                return '';
            }

            var text = String(value).trim();

            if (text === '') {
                return '';
            }

            var parts = text.split(',').map(function (part) {
                return part.trim();
            }).filter(function (part) {
                var lower = part.toLowerCase();
                return part !== '' && lower !== 'null' && lower !== 'undefined' && lower !== 'nil';
            });

            return parts.join(', ');
        }

        /* The whole address as one line. `keys` picks which parts and in what
         * order; the default is how an address reads aloud.
         *
         * Returns '' when there is nothing to show, so the caller can hide the
         * row rather than print an empty label. */
        function spideliFormatAddress(address, keys) {
            if (!address || typeof address !== 'object') {
                return '';
            }

            var order = keys || ['address', 'locality', 'landmark'];
            var seen = {};
            var out = [];

            order.forEach(function (key) {
                var cleaned = spideliCleanAddressPart(address[key]);

                if (cleaned === '') {
                    return;
                }

                /* The same text is often held in two of these fields. Print it
                 * once: "Tsinga, Tsinga" reads like a different kind of bug. */
                var fingerprint = cleaned.toLowerCase();

                if (seen[fingerprint]) {
                    return;
                }

                seen[fingerprint] = true;
                out.push(cleaned);
            });

            return out.join(', ');
        }
</script>
