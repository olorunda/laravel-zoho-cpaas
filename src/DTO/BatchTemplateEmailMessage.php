<?php

namespace Olorunda\ZohoCpaas\DTO;

use InvalidArgumentException;

class BatchTemplateEmailMessage
{
    protected ?string $templateKey = null;
    protected ?string $templateAlias = null;
    protected ?string $mailagentAlias = null;
    protected ?EmailAddress $from = null;
    protected ?string $bounceAddress = null;
    protected array $recipients = []; // Each: ['email_address' => [...], 'merge_info' => [...]]
    protected ?string $subject = null;
    protected ?string $htmlBody = null;
    protected ?string $textBody = null;
    protected array $attachments = [];
    protected array $inlineImages = [];
    protected ?string $clientReference = null;
    protected ?bool $trackClicks = null;
    protected ?bool $trackOpens = null;
    protected array $mimeHeaders = [];

    public static function create(?string $templateKey = null, ?string $templateAlias = null): static
    {
        $instance = new static();
        if ($templateKey !== null) {
            $instance->templateKey($templateKey);
        }
        if ($templateAlias !== null) {
            $instance->templateAlias($templateAlias);
        }
        return $instance;
    }

    public function templateKey(string $key): static
    {
        $this->templateKey = $key;
        return $this;
    }

    public function templateAlias(string $alias): static
    {
        $this->templateAlias = $alias;
        return $this;
    }

    public function mailagentAlias(string $alias): static
    {
        $this->mailagentAlias = $alias;
        return $this;
    }

    public function from(string|array|EmailAddress $address, ?string $name = null): static
    {
        $this->from = EmailAddress::make($address, $name);
        return $this;
    }

    public function bounceAddress(?string $bounceAddress): static
    {
        $this->bounceAddress = $bounceAddress;
        return $this;
    }

    public function to(string|array|EmailAddress $address, ?string $name = null, array $mergeInfo = []): static
    {
        $emailAddr = EmailAddress::make($address, $name);
        $item = [
            'email_address' => $emailAddr->toArray(),
        ];

        if (!empty($mergeInfo)) {
            $item['merge_info'] = $mergeInfo;
        }

        $this->recipients[] = $item;
        return $this;
    }

    public function recipients(array $recipients): static
    {
        foreach ($recipients as $recipient) {
            $address = $recipient['address'] ?? $recipient['email'] ?? $recipient[0] ?? '';
            $name = $recipient['name'] ?? $recipient[1] ?? null;
            $mergeInfo = $recipient['merge_info'] ?? [];
            $this->to($address, $name, $mergeInfo);
        }

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function html(?string $html): static
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function text(?string $text): static
    {
        $this->textBody = $text;
        return $this;
    }

    public function attach(Attachment|string $attachment, ?string $name = null, ?string $mimeType = null): static
    {
        if ($attachment instanceof Attachment) {
            $this->attachments[] = $attachment;
        } else {
            $this->attachments[] = Attachment::fromPath($attachment, $name, $mimeType);
        }

        return $this;
    }

    public function attachData(string $data, string $name, ?string $mimeType = null): static
    {
        $this->attachments[] = Attachment::fromData($data, $name, $mimeType);
        return $this;
    }

    public function attachCache(string $fileCacheKey, string $name, ?string $mimeType = null): static
    {
        $this->attachments[] = Attachment::fromCacheKey($fileCacheKey, $name, $mimeType);
        return $this;
    }

    public function embedImage(InlineImage|string $image, string $cid, ?string $name = null, ?string $mimeType = null): static
    {
        if ($image instanceof InlineImage) {
            $this->inlineImages[] = $image;
        } else {
            $this->inlineImages[] = InlineImage::fromPath($image, $cid, $name, $mimeType);
        }

        return $this;
    }

    public function clientReference(?string $clientReference): static
    {
        $this->clientReference = $clientReference;
        return $this;
    }

    public function trackClicks(?bool $track): static
    {
        $this->trackClicks = $track;
        return $this;
    }

    public function trackOpens(?bool $track): static
    {
        $this->trackOpens = $track;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->mimeHeaders[$key] = $value;
        return $this;
    }

    public function headers(array $headers): static
    {
        $this->mimeHeaders = array_merge($this->mimeHeaders, $headers);
        return $this;
    }

    public function toPayload(array $defaults = []): array
    {
        if ($this->templateKey === null && $this->templateAlias === null) {
            throw new InvalidArgumentException('Batch template email requires either a template_key or template_alias.');
        }

        $from = $this->from;
        if ($from === null && isset($defaults['from']['address'])) {
            $from = new EmailAddress($defaults['from']['address'], $defaults['from']['name'] ?? null);
        }

        if ($from === null) {
            throw new InvalidArgumentException('Batch template email requires a "from" sender address.');
        }

        if (empty($this->recipients)) {
            throw new InvalidArgumentException('Batch template email requires at least one recipient.');
        }

        $payload = [
            'from' => $from->toArray(),
            'to' => $this->recipients,
        ];

        if ($this->templateKey !== null) {
            $payload['template_key'] = $this->templateKey;
        } elseif ($this->templateAlias !== null) {
            $payload['template_alias'] = $this->templateAlias;
        }

        $mailagent = $this->mailagentAlias ?? $defaults['mailagent_alias'] ?? null;
        if (!empty($mailagent)) {
            $payload['mailagent_alias'] = $mailagent;
        }

        if ($this->subject !== null) {
            $payload['subject'] = $this->subject;
        }

        if ($this->htmlBody !== null) {
            $payload['htmlbody'] = $this->htmlBody;
        }

        if ($this->textBody !== null) {
            $payload['textbody'] = $this->textBody;
        }

        $bounce = $this->bounceAddress ?? $defaults['bounce_address'] ?? null;
        if (!empty($bounce)) {
            $payload['bounce_address'] = $bounce;
        }

        if (!empty($this->attachments)) {
            $payload['attachments'] = array_map(fn (Attachment $att) => $att->toArray(), $this->attachments);
        }

        if (!empty($this->inlineImages)) {
            $payload['inline_images'] = array_map(fn (InlineImage $img) => $img->toArray(), $this->inlineImages);
        }

        if ($this->clientReference !== null) {
            $payload['client_reference'] = $this->clientReference;
        }

        $trackClicks = $this->trackClicks ?? $defaults['tracking']['track_clicks'] ?? null;
        if ($trackClicks !== null) {
            $payload['track_clicks'] = (bool) $trackClicks;
        }

        $trackOpens = $this->trackOpens ?? $defaults['tracking']['track_opens'] ?? null;
        if ($trackOpens !== null) {
            $payload['track_opens'] = (bool) $trackOpens;
        }

        if (!empty($this->mimeHeaders)) {
            $payload['mime_headers'] = $this->mimeHeaders;
        }

        return $payload;
    }
}
