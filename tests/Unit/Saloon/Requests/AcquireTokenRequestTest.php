<?php

use Maize\MsgraphMail\Saloon\Requests\AcquireTokenRequest;

test('acquire token request has correct endpoint', function () {
    $request = new AcquireTokenRequest(
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    expect($request->resolveEndpoint())->toBe('/oauth2/v2.0/token');
});

test('acquire token request has correct body', function () {
    $request = new AcquireTokenRequest(
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        retryAttempts: 3,
        retryDelay: 1000
    );

    $body = $request->body()->all();

    expect($body)->toMatchArray([
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials',
    ]);
});

test('acquire token request can use custom scope', function () {
    $request = new AcquireTokenRequest(
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        cacheTtl: 3300,
        scope: 'custom-scope',
        retryAttempts: 3,
        retryDelay: 1000
    );

    $body = $request->body()->all();

    expect($body['scope'])->toBe('custom-scope');
});
