<?php

declare(strict_types=1);

namespace Kagero\Laravel;

use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;
use Inertia\Middleware;
use Kagero\Laravel\Console\Commands\KageroGenerateCommand;
use Kagero\Laravel\Http\Middleware\HandleInertiaProxyRequests;
use Kagero\Laravel\Http\Middleware\TrustKageroHeaders;

final class KageroServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kagero.php', 'kagero');

        $this->app->bind(TrustProxies::class, TrustKageroHeaders::class);
    }

    public function boot(): void
    {
        $this->commands([KageroGenerateCommand::class]);

        $this->callAfterResolving(HttpKernel::class, function (Kernel $kernel): void {
            $kernel->appendMiddlewareToGroup('web', HandleInertiaProxyRequests::class);

            $kernel->addToMiddlewarePriorityBefore(
                AuthenticatesRequests::class,
                Middleware::class,
            );
            $kernel->addToMiddlewarePriorityAfter(
                Middleware::class,
                HandleInertiaProxyRequests::class,
            );
        });

        if (config('kagero.enabled')) {
            DevCommands::node('dev:kagero', 'kagero');
        }
    }
}
