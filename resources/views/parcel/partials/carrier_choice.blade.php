{{-- Point 41: "Choose a carrier" - the app's carrier step, on the checkout.

     Every option was priced ON THE SERVER when the form was sent
     (App\Services\ParcelPricing), from what is live in Firestore: only the
     customer's region's carriers (decision 3), each in the region's currency.
     Choosing one posts only its key; the server re-applies its own price.

     The price beside each option is what that option costs in all - the
     carrier's price plus its door / pickup-point and fixed charges - so the
     options can be compared fairly. The breakdown below shows the parts. --}}
@php
    $p41Quotes = $parcel_cart['parcelQuotes'] ?? [];
    $p41Selected = $parcel_cart['parcelSelectedKey'] ?? null;
    $p41Details = $parcel_cart['parcelDetails'] ?? [];
    $p41Breakdown = $parcel_cart['priceBreakdown'] ?? null;
    $p41Currency = $parcel_cart['currencyData'] ?? [];
    $p41ScopeLabel = [
        'city' => trans('lang.parcel_scope_city'),
        'intercity' => trans('lang.parcel_scope_intercity'),
        'intercountry' => trans('lang.parcel_scope_intercountry'),
    ][$p41Details['scope'] ?? 'city'] ?? trans('lang.parcel_scope_city');
    $p41Place = function ($place) {
        $place = is_array($place) ? $place : [];
        return implode(', ', array_filter([$place['city'] ?? '', $place['country'] ?? '']));
    };
    $p41From = $p41Place($p41Details['origin'] ?? []);
    $p41To = $p41Place($p41Details['destination'] ?? []);
@endphp
@if(!empty($p41Quotes))
<div class="card p-3 p41-carriers" id="p41_carriers">

    <div class="mb-3 p-3 rounded" style="background:#FFF5EA;">
        <div>
            <span class="p41-chip">{{ $p41ScopeLabel }}</span>
            <span class="ml-2">
                {{ ($p41Details['shipmentType'] ?? 'parcel') === 'mail' ? trans('lang.parcel_shipment_mail') : trans('lang.parcel_shipment_parcel') }}
                @if($p41Breakdown && isset($p41Breakdown['chargeableKg']))
                    - {{ str_replace(':kg', $p41Breakdown['chargeableKg'], trans('lang.parcel_kg_charged')) }}
                @endif
            </span>
        </div>
        @if($p41From !== '' || $p41To !== '')
            <div class="mt-2"><i class="feather-navigation mr-1"></i>{{ $p41From }} &rarr; {{ $p41To }}</div>
        @endif
        @if(!empty($p41Details['dimensions']))
            <div class="small text-muted mt-1">
                {{ $p41Details['dimensions']['l'] }} x {{ $p41Details['dimensions']['w'] }} x {{ $p41Details['dimensions']['h'] }} cm
            </div>
        @endif
        <div class="small text-muted mt-1">
            {{ ($p41Details['pickupMethod'] ?? 'home') === 'pickup_point'
                ? trans('lang.parcel_method_point_dropoff') . (($p41Details['dropoffPointName'] ?? '') !== '' ? ' - ' . $p41Details['dropoffPointName'] : '')
                : trans('lang.parcel_method_home_pickup') }}
            &middot;
            {{ ($p41Details['deliveryMethod'] ?? 'home') === 'pickup_point'
                ? trans('lang.parcel_method_point_collection') . (($p41Details['pickupPointName'] ?? '') !== '' ? ' - ' . $p41Details['pickupPointName'] : '')
                : trans('lang.parcel_method_home_delivery') }}
        </div>
    </div>

    <h5 class="mb-0">{{ trans('lang.parcel_choose_carrier') }}</h5>
    <small class="text-muted d-block mb-2">{{ str_replace(':count', count($p41Quotes), trans('lang.parcel_carriers_available')) }}</small>

    @foreach($p41Quotes as $quote)
        @php
            $isSelected = ($quote['key'] ?? null) === $p41Selected;
            $optionTotal = ($quote['priceBreakdown']['carrierPrice'] ?? 0) + ($quote['extras'] ?? 0);
            $eta = $quote['eta'] ?? null;
            $etaText = '';
            if ($eta && ($eta['min'] !== null || $eta['max'] !== null)) {
                $etaText = str_replace(
                    [':min', ':max', ':unit'],
                    [$eta['min'] ?? $eta['max'], $eta['max'] ?? $eta['min'],
                     ($eta['unit'] ?? 'hours') === 'days' ? trans('lang.parcel_unit_days') : trans('lang.parcel_unit_hours')],
                    trans('lang.parcel_estimated_time'));
            }
        @endphp
        <label class="p41-carrier {{ $isSelected ? 'is-selected' : '' }}">
            <input type="radio" name="p41_carrier" value="{{ $quote['key'] }}" class="d-none" {{ $isSelected ? 'checked' : '' }}>
            @if(!empty($quote['photo']))
                <img src="{{ $quote['photo'] }}" alt="">
            @else
                <span class="p41-carrier-icon"><i class="feather-truck"></i></span>
            @endif
            <span>
                <span class="p41-carrier-name d-block">{{ $quote['type'] === 'default' ? trans('lang.parcel_spideli_drivers') : $quote['carrierName'] }}</span>
                <span class="p41-carrier-sub d-block">
                    @if($quote['type'] === 'default')
                        {{ trans('lang.parcel_standard_city_delivery') }}
                    @elseif($etaText !== '')
                        <i class="feather-clock mr-1"></i>{{ $etaText }}
                    @endif
                </span>
            </span>
            <span class="p41-carrier-price">{{ formatCurrency($optionTotal, $p41Currency) }}</span>
        </label>
    @endforeach

    @if($p41Breakdown)
        <div class="mt-2 p-3 border rounded p41-breakdown">
            <div class="small text-uppercase text-muted mb-1"><i class="feather-file-text mr-1"></i>{{ trans('lang.parcel_shipping_price') }}</div>
            <div class="d-flex"><span>{{ trans('lang.parcel_carrier_price') }}</span><span class="ml-auto">{{ formatCurrency($p41Breakdown['carrierPrice'] ?? 0, $p41Currency) }}</span></div>
            @if(($p41Breakdown['pickupCharge'] ?? 0) > 0)
                <div class="d-flex"><span>{{ ($p41Details['pickupMethod'] ?? 'home') === 'pickup_point' ? trans('lang.parcel_method_point_dropoff') : trans('lang.parcel_method_home_pickup') }}</span><span class="ml-auto">+{{ formatCurrency($p41Breakdown['pickupCharge'], $p41Currency) }}</span></div>
            @endif
            @if(($p41Breakdown['deliveryCharge'] ?? 0) > 0)
                <div class="d-flex"><span>{{ ($p41Details['deliveryMethod'] ?? 'home') === 'pickup_point' ? trans('lang.parcel_method_point_collection') : trans('lang.parcel_method_home_delivery') }}</span><span class="ml-auto">+{{ formatCurrency($p41Breakdown['deliveryCharge'], $p41Currency) }}</span></div>
            @endif
            @if(($p41Breakdown['fixedTax'] ?? 0) > 0)
                <div class="d-flex"><span>{{ ($p41Details['scope'] ?? '') === 'intercountry' ? trans('lang.parcel_fixed_intercountry') : trans('lang.parcel_fixed_intercity') }}</span><span class="ml-auto">+{{ formatCurrency($p41Breakdown['fixedTax'], $p41Currency) }}</span></div>
            @endif
            <hr class="my-2">
            <div class="d-flex font-weight-bold"><span>{{ trans('lang.parcel_shipping_total') }}</span><span class="ml-auto" style="color:#D17E19;">{{ formatCurrency(($p41Breakdown['carrierPrice'] ?? 0) + ($p41Breakdown['options'] ?? 0) + ($p41Breakdown['fixedTax'] ?? 0), $p41Currency) }}</span></div>
        </div>
    @endif
</div>
@endif
