<?php

namespace Maize\MsgraphMailer\Saloon\Connectors;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class MicrosoftGraphConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        private string $tenantId,
    ) {}

    public function resolveBaseUrl(): string
    {
        return "https://login.microsoftonline.com/{$this->tenantId}";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];
    }
}
