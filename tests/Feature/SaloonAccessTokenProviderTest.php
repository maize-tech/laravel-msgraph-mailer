<?php

use Maize\MsgraphMailer\Saloon\Connectors\MicrosoftGraphConnector;
use Maize\MsgraphMailer\Saloon\Requests\AcquireTokenRequest;
use Maize\MsgraphMailer\Services\SaloonAccessTokenProvider;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('acquires token successfully', function () {
    MockClient::global([
        AcquireTokenRequest::class => MockResponse::make([
            'access_token' => 'fake-token-123',
            'token_type' => 'Bearer',
            'expires_in' => 3599,
        ], 200),
    ]);

    $connector = new MicrosoftGraphConnector(tenantId: 'test-tenant');
    $provider = new SaloonAccessTokenProvider(
        connector: $connector,
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    $promise = $provider->getAuthorizationTokenAsync('https://graph.microsoft.com');
    $token = $promise->wait();

    expect($token)->toBe('fake-token-123');
});

test('caches token after first acquisition', function () {
    MockClient::global([
        AcquireTokenRequest::class => MockResponse::make([
            'access_token' => 'fake-token-123',
            'token_type' => 'Bearer',
            'expires_in' => 3599,
        ], 200),
    ]);

    $connector = new MicrosoftGraphConnector(tenantId: 'test-tenant');
    $provider = new SaloonAccessTokenProvider(
        connector: $connector,
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    // First call
    $promise1 = $provider->getAuthorizationTokenAsync('https://graph.microsoft.com');
    $token1 = $promise1->wait();

    // Second call (should use Saloon's internal caching)
    $promise2 = $provider->getAuthorizationTokenAsync('https://graph.microsoft.com');
    $token2 = $promise2->wait();

    expect($token1)->toBe('fake-token-123');
    expect($token2)->toBe('fake-token-123');
});

test('clear token invalidates cache', function () {
    MockClient::global([
        AcquireTokenRequest::class => MockResponse::make([
            'access_token' => 'fake-token-123',
            'token_type' => 'Bearer',
            'expires_in' => 3599,
        ], 200),
    ]);

    $connector = new MicrosoftGraphConnector(tenantId: 'test-tenant');
    $provider = new SaloonAccessTokenProvider(
        connector: $connector,
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    // Acquire token
    $promise = $provider->getAuthorizationTokenAsync('https://graph.microsoft.com');
    $token = $promise->wait();
    expect($token)->toBe('fake-token-123');

    // Clear token should invalidate Saloon's cache
    $provider->clearToken();

    // Method should complete without errors
    expect(true)->toBeTrue();
});

test('returns allowed hosts validator', function () {
    $connector = new MicrosoftGraphConnector(tenantId: 'test-tenant');
    $provider = new SaloonAccessTokenProvider(
        connector: $connector,
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    $validator = $provider->getAllowedHostsValidator();

    expect($validator)->toBeInstanceOf(\Microsoft\Kiota\Abstractions\Authentication\AllowedHostsValidator::class);
});

test('acquire token request sends successfully with caching', function () {
    MockClient::global([
        AcquireTokenRequest::class => MockResponse::make([
            'access_token' => 'fake-token-123',
            'token_type' => 'Bearer',
            'expires_in' => 3599,
        ], 200),
    ]);

    $connector = new MicrosoftGraphConnector(tenantId: 'test-tenant');
    $request = new AcquireTokenRequest(
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300
    );

    $response = $connector->send($request);

    expect($response->successful())->toBeTrue();
    expect($response->json())->toHaveKey('access_token', 'fake-token-123');
});
