<?php

namespace App\Http\Middleware;

use App\Services\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        if (! InstallState::isInstalled()) {
            return redirect()->route('install.requirements');
        }

        return $next($request);
    }
}
