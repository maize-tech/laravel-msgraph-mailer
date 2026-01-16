<?php

namespace Maize\MsgraphMailer\Saloon\Requests;

use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Contracts\Driver;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\RequestProperties\HasTries;

class AcquireTokenRequest extends Request implements Cacheable, HasBody
{
    use AlwaysThrowOnErrors;
    use HasCaching;
    use HasFormBody;
    use HasTries;

    protected Method $method = Method::POST;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private int $cacheTtl = 3300,
        private string $scope = 'https://graph.microsoft.com/.default',
        int $retryAttempts = 3,
        int $retryDelay = 1000,
    ) {
        $this->tries = $retryAttempts;
        $this->retryInterval = $retryDelay;
        $this->throwOnMaxTries = true;
    }

    public function resolveEndpoint(): string
    {
        return '/oauth2/v2.0/token';
    }

    protected function defaultBody(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => $this->scope,
            'grant_type' => 'client_credentials',
        ];
    }

    public function resolveCacheDriver(): Driver
    {
        return new LaravelCacheDriver(app()->make('cache')->store());
    }

    public function cacheExpiryInSeconds(): int
    {
        return $this->cacheTtl;
    }

    protected function cacheKey(PendingRequest $pendingRequest): ?string
    {
        return sprintf(
            'microsoft_graph_token:%s:%s',
            $this->clientId,
            md5($this->scope)
        );
    }

    protected function getCacheableMethods(): array
    {
        return [Method::POST, Method::GET];
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        // Invalidate cache before retry to force fresh token acquisition
        if ($request instanceof self) {
            $request->invalidateCache();
        }

        // Always retry on failure
        return true;
    }
}
