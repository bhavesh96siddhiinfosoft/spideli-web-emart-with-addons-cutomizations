<!DOCTYPE html>
{{-- #114: a client subscribes to a provider's plan and chooses how to pay (wallet or the client's gateways, #92). --}}
@php
    $cur = $offer['currencyData'];
    $money = function ($v) use ($cur, $offer) { return formatCurrency($v, $cur + ['decimal_degits' => \App\Services\ProviderSubscription::decimals($offer['currency'])]); };
    $plan = $offer['plan'];
    $points = array_values(array_filter(array_map('strval', (array)($plan['plan_points'] ?? [])), 'strlen'));
@endphp
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('lang.provider_sub_title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f6f8;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #222; padding: 16px; box-sizing: border-box; }
        .box { background: #fff; border-radius: 12px; padding: 28px; max-width: 460px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .by { color: #777; font-size: 14px; margin: 0 0 16px; }
        .price { font-size: 26px; font-weight: 700; margin: 0; }
        .period { color: #555; font-size: 14px; margin: 0 0 14px; }
        .desc { color: #444; line-height: 1.5; margin: 0 0 12px; white-space: pre-wrap; }
        ul { margin: 0 0 16px; padding-left: 20px; color: #333; line-height: 1.6; }
        .methods { border-top: 1px solid #eee; padding-top: 14px; margin-top: 8px; }
        .methods h2 { font-size: 15px; margin: 0 0 10px; }
        label.m { display: flex; align-items: center; gap: 10px; border: 1px solid #e3e6ea; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; cursor: pointer; }
        label.m small { color: #888; margin-left: auto; }
        button { width: 100%; background: #2e9e7f; color: #fff; border: 0; border-radius: 8px; padding: 13px; font-size: 16px; margin-top: 8px; cursor: pointer; }
        .err { background: #fdecec; color: #a33; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; }
        .note { color: #888; font-size: 12px; margin-top: 12px; line-height: 1.5; }
        a.back { display: block; text-align: center; margin-top: 14px; color: #2a7d6c; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        @if($error)
            <div class="err">{{ $error }}</div>
        @endif
        <h1>{{ $plan['title'] ?? '' }}</h1>
        <p class="by">{{ trans('lang.provider_sub_by', ['name' => $offer['providerName']]) }}</p>
        <p class="price">{{ $money($offer['price']) }}</p>
        <p class="period">{{ $offer['days'] === 365 ? trans('lang.provider_sub_per_year') : trans('lang.provider_sub_per_month') }}</p>
        @if(!empty($plan['description']))
            <p class="desc">{{ $plan['description'] }}</p>
        @endif
        @if($points)
            <ul>
                @foreach($points as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('provider-subscription.pay', $offer['planId']) }}" class="methods">
            @csrf
            <h2>{{ trans('lang.provider_sub_pay_with') }}</h2>
            @foreach($methods as $key => $label)
                <label class="m">
                    <input type="radio" name="method" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                    <span>{{ $label }}</span>
                    @if($key === 'wallet')
                        <small>{{ trans('lang.provider_sub_balance', ['amount' => $money($balance)]) }}</small>
                    @endif
                </label>
            @endforeach
            <button type="submit">{{ trans('lang.provider_sub_pay', ['amount' => $money($offer['price'])]) }}</button>
        </form>
        <p class="note">{{ trans('lang.provider_sub_note') }}</p>
        <a class="back" href="{{ route('ondemand-providerdetail', $offer['providerId']) }}">{{ trans('lang.provider_sub_back') }}</a>
    </div>
</body>
</html>
