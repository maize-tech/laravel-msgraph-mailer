<?php

namespace Maize\MsgraphMailer\Transport;

use Maize\MsgraphMailer\Exceptions\MicrosoftGraphException;
use Maize\MsgraphMailer\Services\MicrosoftGraphClient;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MicrosoftGraphTransport implements TransportInterface
{
    public function __construct(
        private MicrosoftGraphClient $client
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if (! $message instanceof Email) {
            throw new MicrosoftGraphException(
                'MicrosoftGraphTransport only supports Email messages'
            );
        }

        $this->client->sendEmail($message);

        return new SentMessage($message, $envelope ?? Envelope::create($message));
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
