<!DOCTYPE html>
{{-- #92: shared by the payment gateways (MyPVIT today): shown while the payment is not confirmed yet;
     checks again every 5 seconds. The caller may give the text ($text). --}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5;url={{ $retryUrl }}">
    <title>{{ trans('lang.mycoolpay_waiting_title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f6f8;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #222; padding: 16px; box-sizing: border-box; }
        .box { background: #fff; border-radius: 12px; padding: 32px 28px; max-width: 420px; width: 100%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        .spin { width: 42px; height: 42px; margin: 0 auto 18px; border: 4px solid #e3e6ea; border-top-color: #4fbfa8; border-radius: 50%; animation: s 1s linear infinite; }
        @keyframes s { to { transform: rotate(360deg); } }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { margin: 0 0 18px; color: #555; line-height: 1.5; }
        a { color: #2a7d6c; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spin"></div>
        <h1>{{ trans('lang.mycoolpay_waiting_title') }}</h1>
        <p>{{ $text ?? trans('lang.mycoolpay_waiting_text') }}</p>
        <a href="{{ $retryUrl }}">{{ trans('lang.mycoolpay_check_now') }}</a>
    </div>
</body>
</html>
