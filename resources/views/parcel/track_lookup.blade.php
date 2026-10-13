{{--
    Track a parcel by its tracking number (SPD-YYMMDD-XXXXXX) - as the app's
    "Track a parcel" (PARCEL-MANAGEMENT-FLOW 4.5). Public, like the tracking
    page: the person asking is often the receiver. A number that matches a
    parcel goes straight to that parcel's tracking page.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ trans('lang.parcel_tracking_title') }} &middot; <?php echo env('APP_NAME'); ?></title>
    <link rel="icon" type="image/png" href="{{ asset('img/spideli-circle.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/font-awesome.min.css') }}" rel="stylesheet">
    @include('parcel.partials.tracking_styles')
</head>
<body>

<div class="track-bar">
    <div class="container">
        <strong><?php echo env('APP_NAME'); ?></strong>
        <span class="float-right small">{{ trans('lang.parcel_tracking_title') }}</span>
    </div>
</div>

<div class="container" style="max-width: 640px;">
    <div class="track-card">
        <h5 class="font-weight-bold mb-2">{{ trans('lang.parcel_track_number_title') }}</h5>
        <p class="text-muted small">{{ trans('lang.parcel_track_number_help') }}</p>
        @if (!empty($notFound))
            <div class="alert alert-warning py-2 small">{{ trans('lang.parcel_track_number_not_found') }}</div>
        @endif
        {{-- PARCEL-MANAGEMENT-FLOW 4.5: the app tracks by tracking number too. --}}
        <form method="GET" action="{{ route('parcel_tracking_lookup') }}" class="d-flex">
            <input type="text" name="number" class="form-control mr-2" maxlength="40" required
                   placeholder="{{ trans('lang.parcel_track_number_placeholder') }}" value="{{ $number ?? '' }}">
            <button type="submit" class="btn btn-dark text-nowrap">{{ trans('lang.parcel_track_number_button') }}</button>
        </form>
    </div>
</div>

</body>
</html>
