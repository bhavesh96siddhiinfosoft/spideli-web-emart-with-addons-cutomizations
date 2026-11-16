<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // #92: MyCoolPay posts its payment notices here (checked by IP and MD5 signature instead).
        'mycoolpay/callback/*',
        // #92: PVit posts its payment notices here (checked by IP, then confirmed with PVit's status API).
        'mypvit/callback/*',
    ];
}