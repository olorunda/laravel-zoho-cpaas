<?php

namespace Olorunda\ZohoCpaas\Services;

use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;

class LogService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Get all email logs.
     * GET /v1.1/email
     */
    public function all(array $queryParams = []): array
    {
        return $this->client->oauthApi('GET', '/email', [
            'query' => $queryParams,
        ]);
    }

    /**
     * Get specific email details by email reference.
     * GET /v1.1/email/email-reference/:email-reference
     */
    public function get(string $emailReference): array
    {
        return $this->client->oauthApi('GET', "/email/email-reference/{$emailReference}");
    }
}
