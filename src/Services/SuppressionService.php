<?php

namespace Olorunda\ZohoCpaas\Services;

use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;

class SuppressionService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Get suppression list for a dnd_type (e.g., 'email_address', 'domain', 'soft_bounce').
     * GET /v1.1/suppressions/:dnd_type
     */
    public function get(string $dndType = 'email_address', array $params = []): array
    {
        return $this->client->oauthApi('GET', "/suppressions/{$dndType}", [
            'query' => $params,
        ]);
    }

    /**
     * Add entries to suppression list.
     * POST /v1.1/suppressions/:dnd_type
     *
     * @param string $dndType Type of DND ('email_address', 'domain', etc.)
     * @param array $values List of email addresses or domains
     * @param string $action Action: 'reject', 'suppress', or 'suppress_tracking'
     * @param array $mailagentKeys Optional specific mail agent keys
     * @param string $description Optional description
     */
    public function add(string $dndType, array $values, string $action = 'suppress', array $mailagentKeys = [], string $description = ''): array
    {
        return $this->client->oauthApi('POST', "/suppressions/{$dndType}", [
            'json' => [
                'action' => $action,
                'mailagent_keys' => $mailagentKeys,
                'description' => $description,
                'values' => $values,
            ],
        ]);
    }

    /**
     * Edit suppression entries.
     * PUT /v1.1/suppressions/:dnd_type
     */
    public function update(string $dndType, array $values, string $action = 'suppress', array $mailagentKeys = [], string $description = ''): array
    {
        return $this->client->oauthApi('PUT', "/suppressions/{$dndType}", [
            'json' => [
                'action' => $action,
                'mailagent_keys' => $mailagentKeys,
                'description' => $description,
                'values' => $values,
            ],
        ]);
    }

    /**
     * Delete suppression entries.
     * DELETE /v1.1/suppressions/:dnd_type
     */
    public function delete(string $dndType, array $values): array
    {
        return $this->client->oauthApi('DELETE', "/suppressions/{$dndType}", [
            'json' => [
                'values' => $values,
            ],
        ]);
    }
}
