<!DOCTYPE html>
{{-- Finance F14: the end of a Finance top-up opened from the Spideli app. The app follows finance_topups/{id}.status. --}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('lang.finance_topup_title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f6f8;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #222; padding: 16px; box-sizing: border-box; }
        .box { background: #fff; border-radius: 12px; padding: 32px 28px; max-width: 420px; width: 100%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        .icon { width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 30px; color: #fff; }
        .ok { background: #2e9e7f; } .ko { background: #c94040; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { margin: 0 0 8px; color: #555; line-height: 1.5; }
        .hint { font-size: 13px; color: #888; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon {{ $ok ? 'ok' : 'ko' }}">{{ $ok ? '✓' : '!' }}</div>
        <h1>{{ $ok ? trans('lang.finance_topup_success_title') : trans('lang.finance_topup_failed_title') }}</h1>
        <p>{{ $message }}</p>
        <p class="hint">{{ trans('lang.finance_topup_back_to_app') }}</p>
    </div>
</body>
</html>
