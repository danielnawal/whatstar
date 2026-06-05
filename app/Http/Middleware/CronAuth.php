<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CronAuth
{
    public function handle(Request $request, Closure $next)
    {
        $secret = env('CRON_SECRET_TOKEN');

        // Si no hay token configurado o el token enviado no coincide, rechazar
        if (empty($secret) || $request->input('token') !== $secret) {
            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
