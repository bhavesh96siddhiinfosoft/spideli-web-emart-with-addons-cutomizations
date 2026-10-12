{{-- Point 41: the parcel's details and how it travels at each end - the
     app's "Parcel details" and "Pickup method" steps, in this site's style.
     Included once per tab; $tab keeps each tab's radio groups separate. Every
     field is read by class within the active tab, like the rest of the form.

     Field names saved on the order are the app's: weightKg, dimensions,
     declaredValue, contentDescription, pickupMethod, deliveryMethod,
     dropoffPointId, pickupPointId. --}}
<div class="tab-inner">
    <div class="tab-title sende-title">
        <h3><span>{{ $number }}</span>{{ trans('lang.parcel_details_title') }}</h3>
    </div>
    <div class="row">
        <div class="inputField col-md-6">
            <input type="number" step="any" min="0" class="form-control p41-weight-kg" placeholder=" ">
            <span>{{ trans('lang.parcel_weight_kg') }}</span>
        </div>
        <div class="col-md-6">
            <div class="row">
                <div class="inputField col-4">
                    <input type="number" step="any" min="0" class="form-control p41-dim" data-dim="l" placeholder=" ">
                    <span>{{ trans('lang.parcel_length_cm') }}</span>
                </div>
                <div class="inputField col-4">
                    <input type="number" step="any" min="0" class="form-control p41-dim" data-dim="w" placeholder=" ">
                    <span>{{ trans('lang.parcel_width_cm') }}</span>
                </div>
                <div class="inputField col-4">
                    <input type="number" step="any" min="0" class="form-control p41-dim" data-dim="h" placeholder=" ">
                    <span>{{ trans('lang.parcel_height_cm') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 mb-3">
            <small class="text-muted">{{ trans('lang.parcel_bulky_help') }}</small>
        </div>
        <div class="inputField col-md-6">
            <input type="number" step="any" min="0" class="form-control p41-declared-value" placeholder=" ">
            <span>{{ trans('lang.parcel_declared_value') }}</span>
        </div>
        <div class="inputField col-md-6">
            <input type="text" class="form-control p41-content-description" maxlength="200" placeholder=" ">
            <span>{{ trans('lang.parcel_content_description') }}</span>
        </div>
    </div>
</div>

<div class="tab-inner">
    <div class="tab-title sende-title">
        <h3><span>{{ $number + 1 }}</span>{{ trans('lang.parcel_pickup_delivery_title') }}</h3>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="p41-subtitle">{{ trans('lang.parcel_pickup_method') }}</div>
            <div class="p41-choice-group p41-choice-stack" role="radiogroup">
                <label class="p41-choice">
                    <input type="radio" name="pickupMethod_{{ $tab }}" class="p41-pickup-method" value="home" checked>
                    <span><i class="feather-home mr-1"></i>{{ trans('lang.parcel_method_home_pickup') }}</span>
                </label>
                <label class="p41-choice">
                    <input type="radio" name="pickupMethod_{{ $tab }}" class="p41-pickup-method" value="pickup_point">
                    <span><i class="feather-map-pin mr-1"></i>{{ trans('lang.parcel_method_point_dropoff') }}</span>
                </label>
            </div>
            <select class="form-control mt-2 p41-dropoff-point p41-point-select" style="display:none;">
                <option value="">{{ trans('lang.parcel_choose_point') }}</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <div class="p41-subtitle">{{ trans('lang.parcel_delivery_method') }}</div>
            <div class="p41-choice-group p41-choice-stack" role="radiogroup">
                <label class="p41-choice">
                    <input type="radio" name="deliveryMethod_{{ $tab }}" class="p41-delivery-method" value="home" checked>
                    <span><i class="feather-home mr-1"></i>{{ trans('lang.parcel_method_home_delivery') }}</span>
                </label>
                <label class="p41-choice">
                    <input type="radio" name="deliveryMethod_{{ $tab }}" class="p41-delivery-method" value="pickup_point">
                    <span><i class="feather-map-pin mr-1"></i>{{ trans('lang.parcel_method_point_collection') }}</span>
                </label>
            </div>
            <select class="form-control mt-2 p41-pickup-point p41-point-select" style="display:none;">
                <option value="">{{ trans('lang.parcel_choose_point') }}</option>
            </select>
        </div>
        <div class="col-12">
            <small class="text-muted p41-no-points" style="display:none;">{{ trans('lang.parcel_no_points_in_region') }}</small>
        </div>
    </div>
</div>
