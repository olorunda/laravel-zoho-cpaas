<?php

namespace Olorunda\ZohoCpaas\Services;

use InvalidArgumentException;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use SplFileInfo;

class EmailService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Send a single transactional email.
     * POST /v1.1/email
     */
    public function send(EmailMessage|array $message): array
    {
        $payload = $message instanceof EmailMessage
            ? $message->toPayload($this->config)
            : $message;

        return $this->client->sendMailApi('POST', '/email', [
            'json' => $payload,
        ]);
    }

    /**
     * Send batch transactional emails.
     * POST /v1.1/email/batch
     */
    public function sendBatch(BatchEmailMessage|array $batchMessage): array
    {
        $payload = $batchMessage instanceof BatchEmailMessage
            ? $batchMessage->toPayload($this->config)
            : $batchMessage;

        return $this->client->sendMailApi('POST', '/email/batch', [
            'json' => $payload,
        ]);
    }

    /**
     * Send email using a predefined template.
     * POST /v1.1/email/template
     */
    public function sendTemplate(TemplateEmailMessage|array $templateMessage): array
    {
        $payload = $templateMessage instanceof TemplateEmailMessage
            ? $templateMessage->toPayload($this->config)
            : $templateMessage;

        return $this->client->sendMailApi('POST', '/email/template', [
            'json' => $payload,
        ]);
    }

    /**
     * Send batch emails using a predefined template.
     * POST /v1.1/email/template/batch
     */
    public function sendBatchTemplate(BatchTemplateEmailMessage|array $batchTemplateMessage): array
    {
        $payload = $batchTemplateMessage instanceof BatchTemplateEmailMessage
            ? $batchTemplateMessage->toPayload($this->config)
            : $batchTemplateMessage;

        return $this->client->sendMailApi('POST', '/email/template/batch', [
            'json' => $payload,
        ]);
    }

    /**
     * Upload a file to ZeptoMail File Cache.
     * POST /v1.1/files?name={name}
     *
     * @param string|resource|SplFileInfo $file File path, stream resource, or file contents
     * @param string $name Name to identify file
     * @return array Response containing file_cache_key
     */
    public function uploadFileCache(mixed $file, string $name): array
    {
        $contents = null;

        if ($file instanceof SplFileInfo) {
            $contents = file_get_contents($file->getPathname());
        } elseif (is_resource($file)) {
            $contents = stream_get_contents($file);
        } elseif (is_string($file)) {
            if (file_exists($file)) {
                $contents = file_get_contents($file);
            } else {
                $contents = $file;
            }
        }

        if ($contents === null || $contents === false) {
            throw new InvalidArgumentException('Invalid file provided for ZeptoMail file cache upload.');
        }

        return $this->client->sendMailApi('POST', '/files', [
            'query' => ['name' => $name],
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $contents,
        ]);
    }
}
