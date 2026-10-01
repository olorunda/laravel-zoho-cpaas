<?php

namespace Olorunda\ZohoCpaas;

use Illuminate\Contracts\Foundation\Application;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use Olorunda\ZohoCpaas\Services\AgentService;
use Olorunda\ZohoCpaas\Services\DomainService;
use Olorunda\ZohoCpaas\Services\EmailService;
use Olorunda\ZohoCpaas\Services\LogService;
use Olorunda\ZohoCpaas\Services\OAuthService;
use Olorunda\ZohoCpaas\Services\SuppressionService;
use Olorunda\ZohoCpaas\Services\TemplateService;

class ZohoCpaasManager implements ZohoCpaasClientInterface
{
    protected Application $app;
    protected ?ZohoCpaasClient $client = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function client(): ZohoCpaasClient
    {
        if ($this->client === null) {
            $config = $this->app['config']->get('zoho-cpaas', []);
            $tokenStore = $this->app->make(TokenStoreInterface::class);
            $this->client = new ZohoCpaasClient($config, $tokenStore);
        }

        return $this->client;
    }

    /**
     * Quick shortcut to send an email.
     */
    public function send(EmailMessage|array $message): array
    {
        return $this->client()->emails()->send($message);
    }

    /**
     * Quick shortcut to send batch emails.
     */
    public function sendBatch(BatchEmailMessage|array $batchMessage): array
    {
        return $this->client()->emails()->sendBatch($batchMessage);
    }

    /**
     * Quick shortcut to send an email using template.
     */
    public function sendTemplate(TemplateEmailMessage|array $templateMessage): array
    {
        return $this->client()->emails()->sendTemplate($templateMessage);
    }

    /**
     * Quick shortcut to send batch emails using template.
     */
    public function sendBatchTemplate(BatchTemplateEmailMessage|array $batchTemplateMessage): array
    {
        return $this->client()->emails()->sendBatchTemplate($batchTemplateMessage);
    }

    public function emails(): EmailService
    {
        return $this->client()->emails();
    }

    public function templates(): TemplateService
    {
        return $this->client()->templates();
    }

    public function suppression(): SuppressionService
    {
        return $this->client()->suppression();
    }

    public function agents(): AgentService
    {
        return $this->client()->agents();
    }

    public function domains(): DomainService
    {
        return $this->client()->domains();
    }

    public function logs(): LogService
    {
        return $this->client()->logs();
    }

    public function oauth(): OAuthService
    {
        return $this->client()->oauth();
    }

    public function sendMailApi(string $method, string $endpoint, array $options = []): array
    {
        return $this->client()->sendMailApi($method, $endpoint, $options);
    }

    public function oauthApi(string $method, string $endpoint, array $options = []): array
    {
        return $this->client()->oauthApi($method, $endpoint, $options);
    }

    public function getApiBaseUrl(): string
    {
        return $this->client()->getApiBaseUrl();
    }

    public function getAccountsBaseUrl(): string
    {
        return $this->client()->getAccountsBaseUrl();
    }

    public function getConfig(): array
    {
        return $this->client()->getConfig();
    }

    /**
     * Dynamically pass methods to the underlying client.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->client()->$method(...$parameters);
    }
}
