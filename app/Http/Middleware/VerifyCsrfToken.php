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
        '/install',
        '/install/migrate',
        '/install/verify',
        'appogio/alert',
        'appogio/reminder',
        'appogio/status',
        'appogio/sso',
        'appogio/get-qr/*',
        'appogio/check-session/*',
        'appogio/generate-token/*',
    ];
}
