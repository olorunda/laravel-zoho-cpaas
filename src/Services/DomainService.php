<?php

namespace Olorunda\ZohoCpaas\Services;

use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;

class DomainService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Get all domains.
     * GET /v1.1/domains
     */
    public function all(): array
    {
        return $this->client->oauthApi('GET', '/domains');
    }

    /**
     * Get details of a specific domain.
     * GET /v1.1/domains/:domain_key
     */
    public function get(string $domainKey): array
    {
        return $this->client->oauthApi('GET', "/domains/{$domainKey}");
    }

    /**
     * Add a new domain.
     * POST /v1.1/domains
     */
    public function create(string $domainName, string $subDomainPrefix = 'bounce-zem'): array
    {
        return $this->client->oauthApi('POST', '/domains', [
            'json' => [
                'domain_name' => $domainName,
                'sub_domain_prefix' => $subDomainPrefix,
            ],
        ]);
    }

    /**
     * Edit a domain.
     * PUT /v1.1/domains/:domain_key
     */
    public function update(string $domainKey, array $data): array
    {
        return $this->client->oauthApi('PUT', "/domains/{$domainKey}", [
            'json' => $data,
        ]);
    }

    /**
     * Trigger domain DNS verification.
     * PUT /v1.1/domains/:domain_key/verify
     */
    public function verify(string $domainKey): array
    {
        return $this->client->oauthApi('PUT', "/domains/{$domainKey}/verify");
    }

    /**
     * Delete a domain.
     * DELETE /v1.1/domains/:domain_key
     */
    public function delete(string $domainKey): array
    {
        return $this->client->oauthApi('DELETE', "/domains/{$domainKey}");
    }
}
