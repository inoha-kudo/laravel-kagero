<?php

declare(strict_types=1);

namespace LaravelKagero;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;
use LaravelKagero\Console\Commands\GenerateCommand;
use LaravelKagero\Http\Middleware\HandleInertiaProxyRequests;
use LaravelKagero\Http\Middleware\TrustKageroHeaders;

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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kagero.php' => $this->app->configPath('kagero.php'),
            ], 'kagero-config');

            $this->commands([
                GenerateCommand::class,
            ]);
        }

        $this->callAfterResolving(KernelContract::class, function (Kernel $kernel) {
            $kernel->appendMiddlewareToGroup('web', HandleInertiaProxyRequests::class);
        });
    }
}
