<?php

namespace Maize\MsgraphMail;

use Illuminate\Support\Facades\Mail;
use Maize\MsgraphMail\Saloon\Connectors\MicrosoftGraphConnector;
use Maize\MsgraphMail\Services\MicrosoftGraphClient;
use Maize\MsgraphMail\Services\SaloonAccessTokenProvider;
use Maize\MsgraphMail\Transport\MicrosoftGraphTransport;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MsgraphMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-msgraph-mail');
    }

    public function packageRegistered(): void
    {
        // Register Saloon connector as singleton with named binding
        $this->app->singleton('mail.microsoft-graph.connector', function ($app) {
            /** @var array{tenant_id: string}|null $config */
            $config = config('mail.mailers.microsoft-graph');

            return new MicrosoftGraphConnector(
                tenantId: $config['tenant_id'] ?? '',
            );
        });

        // Register access token provider as singleton with named binding
        $this->app->singleton('mail.microsoft-graph.token-provider', function ($app) {
            /** @var array{client_id: string, client_secret: string}|null $config */
            $config = config('mail.mailers.microsoft-graph');

            return new SaloonAccessTokenProvider(
                connector: $app->make('mail.microsoft-graph.connector'),
                clientId: $config['client_id'] ?? '',
                clientSecret: $config['client_secret'] ?? '',
                cacheTtl: 3300,        // 55 minutes (tokens are valid for 60 minutes)
                retryAttempts: 3,
                retryDelay: 1000       // 1 second
            );
        });

        // Register Graph client as singleton with named binding
        $this->app->singleton('mail.microsoft-graph.client', function ($app) {
            return new MicrosoftGraphClient(
                tokenProvider: $app->make('mail.microsoft-graph.token-provider'),
                fromAddress: config('mail.from.address', '')
            );
        });
    }

    public function packageBooted(): void
    {
        // Register custom mail transport
        Mail::extend('microsoft-graph', function (array $config) {
            return new MicrosoftGraphTransport(
                client: $this->app->make('mail.microsoft-graph.client')
            );
        });
    }
}
