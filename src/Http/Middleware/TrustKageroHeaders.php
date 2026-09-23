<?php

declare(strict_types=1);

namespace Kagero\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

final class TrustKageroHeaders extends TrustProxies
{
    #[\Override]
    public function handle(Request $request, Closure $next): mixed
    {
        if (config('kagero.enabled')) {
            $this->proxies = '*';

            $this->restoreForwardedHeaders($request);
        }

        return parent::handle($request, $next);
    }

    private function restoreForwardedHeaders(Request $request): void
    {
        if ($for = $request->headers->get('X-Kagero-For')) {
            $request->headers->set('X-Forwarded-For', $for);
        }

        if ($host = $request->headers->get('X-Kagero-Host')) {
            $request->headers->set('X-Forwarded-Host', $host);
            $request->headers->remove('X-Forwarded-Port');
        }

        if ($proto = $request->headers->get('X-Kagero-Proto')) {
            $request->headers->set('X-Forwarded-Proto', $proto);
        }
    }
}
