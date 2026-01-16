<?php

namespace Maize\MsgraphMailer;

use Illuminate\Support\Facades\Mail;
use Maize\MsgraphMailer\Saloon\Connectors\MicrosoftGraphConnector;
use Maize\MsgraphMailer\Services\MicrosoftGraphClient;
use Maize\MsgraphMailer\Services\SaloonAccessTokenProvider;
use Maize\MsgraphMailer\Transport\MicrosoftGraphTransport;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MsgraphMailerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-msgraph-mailer');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('mail.microsoft-graph.connector', function ($app) {
            /** @var array{tenant_id: string}|null $config */
            $config = config('mail.mailers.microsoft-graph');

            return new MicrosoftGraphConnector(
                tenantId: $config['tenant_id'] ?? '',
            );
        });

        $this->app->singleton('mail.microsoft-graph.token-provider', function ($app) {
            /** @var array{client_id: string, client_secret: string}|null $config */
            $config = config('mail.mailers.microsoft-graph');

            return new SaloonAccessTokenProvider(
                connector: $app->make('mail.microsoft-graph.connector'),
                clientId: $config['client_id'] ?? '',
                clientSecret: $config['client_secret'] ?? '',
                cacheTtl: 3300, // 55 minutes (tokens are valid for 60 minutes)
                retryAttempts: 3,
                retryDelay: 1000 // 1 second
            );
        });

        $this->app->singleton('mail.microsoft-graph.client', function ($app) {
            return new MicrosoftGraphClient(
                tokenProvider: $app->make('mail.microsoft-graph.token-provider'),
                fromAddress: config('mail.from.address', '')
            );
        });
    }

    public function packageBooted(): void
    {
        Mail::extend('microsoft-graph', function (array $config) {
            return new MicrosoftGraphTransport(
                client: $this->app->make('mail.microsoft-graph.client')
            );
        });
    }
}
