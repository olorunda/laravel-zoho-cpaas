<?php

namespace Olorunda\ZohoCpaas\Services;

use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;

class AgentService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * List all mail agents.
     * GET /v1.1/mailagents
     */
    public function all(): array
    {
        return $this->client->oauthApi('GET', '/mailagents');
    }

    /**
     * Create a new mail agent.
     * POST /v1.1/mailagents
     */
    public function create(string $name, string $description = ''): array
    {
        return $this->client->oauthApi('POST', '/mailagents', [
            'json' => [
                'mailagent_name' => $name,
                'description' => $description,
            ],
        ]);
    }

    /**
     * Edit a mail agent.
     * PUT /v1.1/mailagents/:mailagentkey
     */
    public function update(string $mailagentKey, string $name, string $description = ''): array
    {
        return $this->client->oauthApi('PUT', "/mailagents/{$mailagentKey}", [
            'json' => [
                'mailagent_name' => $name,
                'description' => $description,
            ],
        ]);
    }

    /**
     * Delete a mail agent.
     * DELETE /v1.1/mailagents/:mailagentkey
     */
    public function delete(string $mailagentKey): array
    {
        return $this->client->oauthApi('DELETE', "/mailagents/{$mailagentKey}");
    }

    /**
     * List API keys for a mail agent.
     * GET /v1.1/mailagents/:mailagentkey/apikeys
     */
    public function listApiKeys(string $mailagentKey): array
    {
        return $this->client->oauthApi('GET', "/mailagents/{$mailagentKey}/apikeys");
    }

    /**
     * Generate a new API key for a mail agent.
     * POST /v1.1/mailagents/:mailagentkey/apikeys
     */
    public function generateApiKey(string $mailagentKey): array
    {
        return $this->client->oauthApi('POST', "/mailagents/{$mailagentKey}/apikeys");
    }

    /**
     * Delete an API key.
     * DELETE /v1.1/mailagents/:mailagentkey/apikeys/:id
     */
    public function deleteApiKey(string $mailagentKey, string $id): array
    {
        return $this->client->oauthApi('DELETE', "/mailagents/{$mailagentKey}/apikeys/{$id}");
    }

    /**
     * List SMTP short passwords for a mail agent.
     * GET /v1.1/mailagents/:mailagentkey/short-passwords
     */
    public function listShortPasswords(string $mailagentKey): array
    {
        return $this->client->oauthApi('GET', "/mailagents/{$mailagentKey}/short-passwords");
    }

    /**
     * Generate SMTP short password for a mail agent.
     * POST /v1.1/mailagents/:mailagentkey/short-passwords
     */
    public function generateShortPassword(string $mailagentKey): array
    {
        return $this->client->oauthApi('POST', "/mailagents/{$mailagentKey}/short-passwords");
    }

    /**
     * Delete an SMTP short password.
     * DELETE /v1.1/mailagents/:mailagentkey/short-passwords/:id
     */
    public function deleteShortPassword(string $mailagentKey, string $id): array
    {
        return $this->client->oauthApi('DELETE', "/mailagents/{$mailagentKey}/short-passwords/{$id}");
    }
}
