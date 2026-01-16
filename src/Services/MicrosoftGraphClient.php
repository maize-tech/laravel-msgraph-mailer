<?php

namespace Maize\MsgraphMail\Services;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Log;
use Maize\MsgraphMail\Exceptions\MicrosoftGraphException;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\Authentication\BaseBearerTokenAuthenticationProvider;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class MicrosoftGraphClient
{
    private GraphServiceClient $graphClient;

    public function __construct(
        private SaloonAccessTokenProvider $tokenProvider,
        private string $fromAddress,
    ) {
        $this->initializeGraphClient();
    }

    public function sendEmail(Email $email): void
    {
        $message = $this->convertSymfonyEmailToGraphMessage($email);

        try {
            $requestBody = new SendMailPostRequestBody;
            $requestBody->setMessage($message);
            $requestBody->setSaveToSentItems(true);

            $this->graphClient
                ->users()
                ->byUserId($this->fromAddress)
                ->sendMail()
                ->post($requestBody)
                ->wait();

            Log::info('Email sent via Microsoft Graph', [
                'to' => array_map(fn ($r) => $r->getEmailAddress()->getAddress(), $message->getToRecipients() ?? []),
                'subject' => $message->getSubject(),
            ]);

        } catch (\Exception $e) {
            Log::error('Microsoft Graph email send failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'from' => $this->fromAddress,
                'to' => array_map(fn ($r) => $r->getEmailAddress()->getAddress(), $message->getToRecipients() ?? []),
                'subject' => $message->getSubject(),
            ]);

            throw new MicrosoftGraphException(
                "Failed to send email: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function initializeGraphClient(): void
    {
        $authProvider = new BaseBearerTokenAuthenticationProvider($this->tokenProvider);
        $this->graphClient = GraphServiceClient::createWithAuthenticationProvider($authProvider);
    }

    private function convertSymfonyEmailToGraphMessage(Email $email): Message
    {
        $message = new Message;

        // Subject
        $message->setSubject($email->getSubject());

        // Recipients
        $message->setToRecipients($this->convertAddresses($email->getTo()));

        if ($cc = $email->getCc()) {
            $message->setCcRecipients($this->convertAddresses($cc));
        }

        if ($bcc = $email->getBcc()) {
            $message->setBccRecipients($this->convertAddresses($bcc));
        }

        // Body (HTML preferred, fallback to text)
        $body = new ItemBody;
        $htmlBody = $email->getHtmlBody();

        if ($htmlBody) {
            $body->setContentType(new BodyType(BodyType::HTML));
            $body->setContent($htmlBody);
        } else {
            $body->setContentType(new BodyType(BodyType::TEXT));
            $body->setContent($email->getTextBody());
        }

        $message->setBody($body);

        // Attachments
        if ($attachments = $email->getAttachments()) {
            $message->setAttachments($this->convertAttachments($attachments));
        }

        // Reply-To
        if ($replyTo = $email->getReplyTo()) {
            $message->setReplyTo($this->convertAddresses($replyTo));
        }

        return $message;
    }

    private function convertAddresses(array $addresses): array
    {
        $recipients = [];

        foreach ($addresses as $address) {
            $recipient = new Recipient;
            $emailAddress = new EmailAddress;

            $emailAddress->setAddress($address->getAddress());

            if ($name = $address->getName()) {
                $emailAddress->setName($name);
            }

            $recipient->setEmailAddress($emailAddress);

            $recipients[] = $recipient;
        }

        return $recipients;
    }

    private function convertAttachments(array $attachments): array
    {
        $graphAttachments = [];

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof DataPart) {
                continue;
            }

            $fileAttachment = new FileAttachment;

            $fileAttachment->setName($attachment->getFilename() ?? 'attachment');
            $fileAttachment->setContentType($attachment->getMediaType());
            $fileAttachment->setContentBytes(Utils::streamFor(base64_encode($attachment->getBody())));

            // Handle inline attachments (images in HTML)
            if ($contentId = $attachment->getContentId()) {
                $fileAttachment->setIsInline(true);
                $fileAttachment->setContentId($contentId);
            }

            $graphAttachments[] = $fileAttachment;
        }

        return $graphAttachments;
    }
}
