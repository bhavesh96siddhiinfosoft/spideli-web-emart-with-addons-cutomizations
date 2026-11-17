<!DOCTYPE html>
{{-- #92: PI-SPI payment page (App\Http\Controllers\PiSpiController). The customer gives their PI-SPI alias,
     we send a payment request, they accept it in their bank app; the page reads the status every 3 seconds. --}}
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trans('lang.pispi_pay_title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f6f8;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #222; padding: 16px; box-sizing: border-box; }
        .box { background: #fff; border-radius: 12px; padding: 28px 24px; max-width: 440px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.06); }
        h1 { font-size: 20px; margin: 0 0 4px; text-align: center; }
        .amount { font-size: 26px; font-weight: 700; text-align: center; margin: 6px 0 18px; }
        p { margin: 0 0 14px; color: #555; line-height: 1.5; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input { width: 100%; box-sizing: border-box; padding: 12px; font-size: 15px; border: 1px solid #cfd4da; border-radius: 8px;
            font-family: ui-monospace, Consolas, monospace; }
        input:focus { outline: 2px solid #4fbfa8; border-color: #4fbfa8; }
        .hint { font-size: 13px; color: #777; margin: 6px 0 16px; }
        button { width: 100%; padding: 13px; font-size: 16px; font-weight: 600; border: 0; border-radius: 8px; background: #4fbfa8; color: #fff; cursor: pointer; }
        button[disabled] { opacity: .55; cursor: default; }
        .msg { display: none; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; font-size: 14px; line-height: 1.4; }
        .msg.err { display: block; background: #fdecec; color: #a12020; }
        .msg.info { display: block; background: #eef7f5; color: #22675a; }
        .spin { width: 40px; height: 40px; margin: 6px auto 16px; border: 4px solid #e3e6ea; border-top-color: #4fbfa8; border-radius: 50%; animation: s 1s linear infinite; }
        @keyframes s { to { transform: rotate(360deg); } }
        .center { text-align: center; }
        .back { display: block; text-align: center; margin-top: 16px; color: #2a7d6c; font-size: 14px; }
        #wait { display: none; }
    </style>
</head>
<body>
<div class="box">
    <h1>{{ trans('lang.pispi_pay_title') }}</h1>
    <div class="amount">{{ number_format($amount, 0, ',', ' ') }} F CFA</div>
    <div class="msg {{ session('pispi_notice') ? 'info' : '' }}" id="msg">{{ session('pispi_notice') }}</div>

    <form id="ask" autocomplete="off">
        <label for="alias">{{ trans('lang.pispi_alias_label') }}</label>
        <input type="text" id="alias" name="alias" maxlength="36" spellcheck="false" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
        <div class="hint">{{ trans('lang.pispi_alias_hint') }}</div>
        <button type="submit" id="send">{{ trans('lang.pispi_send') }}</button>
    </form>

    <div id="wait" class="center">
        <div class="spin"></div>
        <p id="waitText">{{ trans('lang.pispi_accept_in_app') }}</p>
    </div>

    <a class="back" href="{{ route('pispi.cancel') }}">{{ trans('lang.pispi_back') }}</a>
</div>
<script>
(function () {
    var ALIAS = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
    var T = {!! json_encode([
        'invalid' => trans('lang.pispi_alias_invalid'),
        'network' => trans('lang.pispi_network'),
        'expired' => trans('lang.pispi_expired'),
        'sentTo' => trans('lang.pispi_sent_to'),
    ]) !!};
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var ask = document.getElementById('ask'), wait = document.getElementById('wait'), msg = document.getElementById('msg');
    var send = document.getElementById('send'), alias = document.getElementById('alias');
    var timer = null, failures = 0;

    function show(text, kind) { msg.textContent = text || ''; msg.className = 'msg' + (text ? ' ' + kind : ''); }
    function waiting(on) { ask.style.display = on ? 'none' : ''; wait.style.display = on ? 'block' : 'none'; }

    function check() {
        fetch({!! json_encode(route('pispi.status')) !!}, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (d) {
                failures = 0;
                if (d.statut === 'IRREVOCABLE' && d.redirect) { window.location.href = d.redirect; return; }
                if (d.statut === 'REJETE' || d.statut === 'NONE') {
                    waiting(false); send.disabled = false; show(d.message || T.expired, 'err'); return;
                }
                if (d.expired) { waiting(false); send.disabled = false; show(T.expired, 'err'); return; }
                timer = setTimeout(check, 3000);
            })
            .catch(function () { failures++; timer = setTimeout(check, failures > 5 ? 8000 : 3000); });
    }

    ask.addEventListener('submit', function (e) {
        e.preventDefault();
        var a = alias.value.trim();
        if (!ALIAS.test(a)) { show(T.invalid, 'err'); return; }
        send.disabled = true; show('', '');
        fetch({!! json_encode(route('pispi.request')) !!}, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({alias: a})
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { send.disabled = false; show(d.message, 'err'); return; }
            if (d.payeurNom) { show(T.sentTo.replace(':name', d.payeurNom), 'info'); }
            waiting(true);
            timer = setTimeout(check, 3000);
        }).catch(function () { send.disabled = false; show(T.network, 'err'); });
    });

    @if ($waiting)
    waiting(true);
    check();
    @endif
})();
</script>
</body>
</html>
