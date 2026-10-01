<?php

namespace Olorunda\ZohoCpaas\Transport;

use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\DTO\Attachment;
use Olorunda\ZohoCpaas\DTO\EmailAddress;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\InlineImage;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use Olorunda\ZohoCpaas\Mail\Headers\ZeptoMailHeader;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class ZohoCpaasTransport extends AbstractTransport
{
    protected ZohoCpaasClientInterface $client;

    public function __construct(
        ZohoCpaasClientInterface $client,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);
        $this->client = $client;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $headers = $email->getHeaders();
        $templateKey = $headers->has(ZeptoMailHeader::TEMPLATE_KEY)
            ? $headers->get(ZeptoMailHeader::TEMPLATE_KEY)->getBodyAsString()
            : null;
        $templateAlias = $headers->has(ZeptoMailHeader::TEMPLATE_ALIAS)
            ? $headers->get(ZeptoMailHeader::TEMPLATE_ALIAS)->getBodyAsString()
            : null;
        $mailagentAlias = $headers->has(ZeptoMailHeader::MAILAGENT_ALIAS)
            ? $headers->get(ZeptoMailHeader::MAILAGENT_ALIAS)->getBodyAsString()
            : null;

        // Determine if sending as template or standard message
        $isTemplate = ($templateKey !== null || $templateAlias !== null);
        $builder = $isTemplate
            ? TemplateEmailMessage::create($templateKey, $templateAlias)
            : EmailMessage::create();

        if ($isTemplate && $mailagentAlias !== null) {
            $builder->mailagentAlias($mailagentAlias);
        }

        // Sender
        $from = $this->getFromAddress($email, $envelope);
        if ($from !== null) {
            $builder->from($from->getAddress(), $from->getName());
        }

        // Recipients
        $to = $email->getTo();
        if (empty($to) && $envelope !== null) {
            foreach ($envelope->getRecipients() as $recipient) {
                $builder->to($recipient->getAddress(), $recipient->getName());
            }
        } else {
            foreach ($to as $address) {
                $builder->to($address->getAddress(), $address->getName());
            }
        }

        // CC, BCC, Reply-To
        foreach ($email->getCc() as $address) {
            $builder->cc($address->getAddress(), $address->getName());
        }
        foreach ($email->getBcc() as $address) {
            $builder->bcc($address->getAddress(), $address->getName());
        }
        foreach ($email->getReplyTo() as $address) {
            $builder->replyTo($address->getAddress(), $address->getName());
        }

        // Subject & Bodies
        $subject = $email->getSubject();
        if ($subject !== null) {
            $builder->subject($subject);
        }

        $htmlBody = $email->getHtmlBody();
        if ($htmlBody !== null) {
            $builder->html(is_resource($htmlBody) ? stream_get_contents($htmlBody) : $htmlBody);
        }

        $textBody = $email->getTextBody();
        if ($textBody !== null) {
            $builder->text(is_resource($textBody) ? stream_get_contents($textBody) : $textBody);
        }

        // Attachments & Inline Images
        foreach ($email->getAttachments() as $attachment) {
            $this->processAttachment($attachment, $builder);
        }

        // Custom Zepto Headers
        if ($headers->has(ZeptoMailHeader::MERGE_INFO)) {
            $mergeRaw = $headers->get(ZeptoMailHeader::MERGE_INFO)->getBodyAsString();
            $decoded = json_decode($mergeRaw, true);
            if (is_array($decoded)) {
                $builder->mergeInfo($decoded);
            }
        }

        if ($headers->has(ZeptoMailHeader::CLIENT_REFERENCE)) {
            $builder->clientReference($headers->get(ZeptoMailHeader::CLIENT_REFERENCE)->getBodyAsString());
        }

        if ($headers->has(ZeptoMailHeader::BOUNCE_ADDRESS)) {
            $builder->bounceAddress($headers->get(ZeptoMailHeader::BOUNCE_ADDRESS)->getBodyAsString());
        }

        if ($headers->has(ZeptoMailHeader::TRACK_CLICKS)) {
            $val = filter_var($headers->get(ZeptoMailHeader::TRACK_CLICKS)->getBodyAsString(), FILTER_VALIDATE_BOOLEAN);
            $builder->trackClicks($val);
        }

        if ($headers->has(ZeptoMailHeader::TRACK_OPENS)) {
            $val = filter_var($headers->get(ZeptoMailHeader::TRACK_OPENS)->getBodyAsString(), FILTER_VALIDATE_BOOLEAN);
            $builder->trackOpens($val);
        }

        // Custom MIME Headers (excluding Symfony and Zepto specific headers)
        $excludeHeaders = [
            'from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type',
            'mime-version', 'message-id', 'date',
            strtolower(ZeptoMailHeader::TEMPLATE_KEY),
            strtolower(ZeptoMailHeader::TEMPLATE_ALIAS),
            strtolower(ZeptoMailHeader::MAILAGENT_ALIAS),
            strtolower(ZeptoMailHeader::MERGE_INFO),
            strtolower(ZeptoMailHeader::CLIENT_REFERENCE),
            strtolower(ZeptoMailHeader::BOUNCE_ADDRESS),
            strtolower(ZeptoMailHeader::TRACK_CLICKS),
            strtolower(ZeptoMailHeader::TRACK_OPENS),
        ];

        foreach ($headers->all() as $header) {
            $name = strtolower($header->getName());
            if (!in_array($name, $excludeHeaders, true)) {
                $builder->header($header->getName(), $header->getBodyAsString());
            }
        }

        // Dispatch Send via EmailService
        if ($isTemplate && $builder instanceof TemplateEmailMessage) {
            $response = $this->client->emails()->sendTemplate($builder);
        } else {
            $response = $this->client->emails()->send($builder);
        }

        // Extract Message ID if returned
        $messageId = $response['data'][0]['details']['message_id']
            ?? $response['data'][0]['message_id']
            ?? $response['request_id']
            ?? null;

        if ($messageId) {
            $message->getOriginalMessage()->getHeaders()->addHeader('X-Zepto-Message-ID', (string) $messageId);
        }
    }

    protected function getFromAddress(Email $email, ?Envelope $envelope): ?Address
    {
        $from = $email->getFrom();
        if (!empty($from)) {
            return $from[0];
        }

        if ($envelope !== null) {
            return $envelope->getSender();
        }

        return null;
    }

    protected function processAttachment(DataPart $part, EmailMessage|TemplateEmailMessage $builder): void
    {
        $body = $part->getBody();
        $encoded = base64_encode($body);
        $name = $part->getFilename() ?: 'attachment';
        $mime = $part->getMediaType() . '/' . $part->getMediaSubtype();
        $disposition = $part->getDisposition();
        $cid = $part->getContentId();

        if ($disposition === 'inline' || !empty($cid)) {
            $cleanCid = $cid ? trim($cid, '<>') : $name;
            $builder->embedImage(new InlineImage($cleanCid, $name, $encoded, $mime), $cleanCid);
        } else {
            $builder->attach(new Attachment($name, $encoded, $mime));
        }
    }

    public function __toString(): string
    {
        return 'zoho-cpaas';
    }
}
