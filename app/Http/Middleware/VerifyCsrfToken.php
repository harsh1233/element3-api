<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'api-c211bf8a.duosecurity.com/*',
        'https://api-c211bf8a.duosecurity.com/*',
        'api-e6b31c76.duosecurity.com/*',
        'https://api-e6b31c76.duosecurity.com/*',
        '/duologin'
    ];
}
