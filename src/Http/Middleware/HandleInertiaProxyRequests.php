<?php

declare(strict_types=1);

namespace LaravelKagero\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

final class HandleInertiaProxyRequests
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (! config('kagero.enabled')) {
            return $next($request);
        }

        $isInertiaRequest = $request->inertia();

        $request->headers->set(Header::INERTIA, 'true');

        Inertia::version($request->headers->get(Header::VERSION));
        Inertia::share([
            'lang' => Inertia::getShared('lang') ?? fn () => str_replace('_', '-', app()->getLocale()),
            'appearance' => Inertia::getShared('appearance') ?? $request->cookie('appearance', 'system'),
        ]);

        $response = $next($request);

        if (! $isInertiaRequest) {
            $request->headers->remove(Header::INERTIA);

            if ($response->getStatusCode() === 409 && filled($location = $response->headers->get(Header::LOCATION))) {
                $response->headers->remove(Header::LOCATION);
                $response->headers->set('Location', $location);
                $response->setStatusCode(302);
            }
        }

        return $response;
    }
}
