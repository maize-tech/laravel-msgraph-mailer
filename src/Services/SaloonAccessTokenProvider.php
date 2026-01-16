<?php

namespace Maize\MsgraphMailer\Services;

use Exception;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use Maize\MsgraphMailer\Exceptions\GraphAuthenticationException;
use Maize\MsgraphMailer\Saloon\Connectors\MicrosoftGraphConnector;
use Maize\MsgraphMailer\Saloon\Requests\AcquireTokenRequest;
use Microsoft\Kiota\Abstractions\Authentication\AccessTokenProvider;
use Microsoft\Kiota\Abstractions\Authentication\AllowedHostsValidator;

class SaloonAccessTokenProvider implements AccessTokenProvider
{
    private AllowedHostsValidator $allowedHostsValidator;

    public function __construct(
        private MicrosoftGraphConnector $connector,
        private string $clientId,
        private string $clientSecret,
        private int $cacheTtl = 3300,
        private int $retryAttempts = 3,
        private int $retryDelay = 1000,
    ) {
        $this->allowedHostsValidator = new AllowedHostsValidator([
            'graph.microsoft.com',
            'graph.microsoft.us',
            'dod-graph.microsoft.us',
            'graph.microsoft.de',
            'microsoftgraph.chinacloudapi.cn',
        ]);
    }

    public function getAuthorizationTokenAsync(string $url, array $additionalAuthenticationContext = []): Promise
    {
        try {
            $token = $this->getAccessToken();

            return new FulfilledPromise($token);
        } catch (Exception $e) {
            return new RejectedPromise($e);
        }
    }

    public function getAllowedHostsValidator(): AllowedHostsValidator
    {
        return $this->allowedHostsValidator;
    }

    private function getAccessToken(): string
    {
        try {
            $request = new AcquireTokenRequest(
                clientId: $this->clientId,
                clientSecret: $this->clientSecret,
                cacheTtl: $this->cacheTtl,
                retryAttempts: $this->retryAttempts,
                retryDelay: $this->retryDelay,
            );

            $response = $this->connector->send($request);

            $data = $response->json();
            $token = $data['access_token'] ?? throw new Exception('No access token in response');

            return $token;

        } catch (Exception $e) {
            throw new GraphAuthenticationException(
                "Failed to acquire Microsoft Graph access token: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function clearToken(): void
    {
        $request = new AcquireTokenRequest(
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            cacheTtl: $this->cacheTtl,
            retryAttempts: $this->retryAttempts,
            retryDelay: $this->retryDelay,
        );

        $request->invalidateCache();

        // Send request to refresh the cache
        try {
            $this->connector->send($request);
        } catch (Exception $e) {
            // Ignore errors during cache invalidation
        }
    }
}
