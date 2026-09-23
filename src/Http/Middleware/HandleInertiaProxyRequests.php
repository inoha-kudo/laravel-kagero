<?php

declare(strict_types=1);

namespace Kagero\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

final class HandleInertiaProxyRequests
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('kagero.enabled')) {
            $request->headers->set(Header::INERTIA, 'true');

            Inertia::version($request->headers->get(Header::VERSION));
            Inertia::share([
                'lang' => str_replace('_', '-', app()->getLocale()),
                'appearance' => $request->cookie('appearance', 'system'),
            ]);
        }

        return $next($request);
    }
}
