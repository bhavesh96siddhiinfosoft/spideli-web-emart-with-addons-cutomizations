{{-- Point 41: "What are you sending?" and "Where to?" - the first step of the
     app's parcel booking, in this site's style. Included once per tab; $tab
     ("asap" / "schedule") keeps each tab's radio groups separate.

     Where to? is CHOSEN BY THE CUSTOMER, as in the app (agreed 12 Oct). It
     decides the delivery type - city / intercity / intercountry - and with it
     which carriers can be offered and whether a fixed charge applies. --}}
<div class="tab-inner">
    <div class="tab-title sende-title">
        <h3><span>1</span>{{ trans('lang.parcel_what_sending') }}</h3>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="p41-choice-group" role="radiogroup" aria-label="{{ trans('lang.parcel_what_sending') }}">
                <label class="p41-choice">
                    <input type="radio" name="shipmentType_{{ $tab }}" class="p41-shipment-type" value="parcel" checked>
                    <span><i class="feather-package mr-1"></i>{{ trans('lang.parcel_shipment_parcel') }}</span>
                </label>
                <label class="p41-choice">
                    <input type="radio" name="shipmentType_{{ $tab }}" class="p41-shipment-type" value="mail">
                    <span><i class="feather-mail mr-1"></i>{{ trans('lang.parcel_shipment_mail') }}</span>
                </label>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="p41-subtitle">{{ trans('lang.parcel_where_to') }}</div>
            <div class="p41-choice-group" role="radiogroup" aria-label="{{ trans('lang.parcel_where_to') }}">
                <label class="p41-choice">
                    <input type="radio" name="scope_{{ $tab }}" class="p41-scope" value="city" checked>
                    <span>{{ trans('lang.parcel_scope_city') }}</span>
                </label>
                <label class="p41-choice">
                    <input type="radio" name="scope_{{ $tab }}" class="p41-scope" value="intercity">
                    <span>{{ trans('lang.parcel_scope_intercity') }}</span>
                </label>
                <label class="p41-choice">
                    <input type="radio" name="scope_{{ $tab }}" class="p41-scope" value="intercountry">
                    <span>{{ trans('lang.parcel_scope_intercountry') }}</span>
                </label>
            </div>
        </div>
    </div>
</div>
