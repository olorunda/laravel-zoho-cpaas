<?php

namespace Olorunda\ZohoCpaas\Services;

use InvalidArgumentException;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;

class TemplateService
{
    protected ZohoCpaasClientInterface $client;
    protected array $config;

    public function __construct(ZohoCpaasClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    protected function resolveMailagentAlias(?string $alias = null): string
    {
        $resolved = $alias ?: ($this->config['mailagent_alias'] ?? null);
        if (empty($resolved)) {
            throw new InvalidArgumentException(
                'Mail agent alias is required. Pass it as a parameter or set ZOHO_CPAAS_MAILAGENT_ALIAS.'
            );
        }
        return $resolved;
    }

    /**
     * Get details of a specific template.
     * GET /v1.1/mailagents/:mailagent-alias/templates/:template-key
     */
    public function get(string $templateKey, ?string $mailagentAlias = null): array
    {
        $alias = $this->resolveMailagentAlias($mailagentAlias);
        return $this->client->oauthApi('GET', "/mailagents/{$alias}/templates/{$templateKey}");
    }

    /**
     * List all templates for a mail agent.
     * GET /v1.1/mailagents/:mailagent-alias/templates?offset=0&limit=25
     */
    public function all(?string $mailagentAlias = null, int $offset = 0, int $limit = 25): array
    {
        $alias = $this->resolveMailagentAlias($mailagentAlias);
        return $this->client->oauthApi('GET', "/mailagents/{$alias}/templates", [
            'query' => [
                'offset' => $offset,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Create a new template.
     * POST /v1.1/mailagents/:mailagent-alias/templates
     *
     * @param array{template_name: string, subject: string, htmlbody: string, template_alias?: string} $data
     */
    public function create(array $data, ?string $mailagentAlias = null): array
    {
        $alias = $this->resolveMailagentAlias($mailagentAlias);
        return $this->client->oauthApi('POST', "/mailagents/{$alias}/templates", [
            'json' => $data,
        ]);
    }

    /**
     * Update an existing template.
     * PUT /v1.1/mailagents/:mailagent-alias/templates/:template-key
     */
    public function update(string $templateKey, array $data, ?string $mailagentAlias = null): array
    {
        $alias = $this->resolveMailagentAlias($mailagentAlias);
        return $this->client->oauthApi('PUT', "/mailagents/{$alias}/templates/{$templateKey}", [
            'json' => $data,
        ]);
    }

    /**
     * Delete a template.
     * DELETE /v1.1/mailagents/:mailagent-alias/templates/:template-key
     */
    public function delete(string $templateKey, ?string $mailagentAlias = null): array
    {
        $alias = $this->resolveMailagentAlias($mailagentAlias);
        return $this->client->oauthApi('DELETE', "/mailagents/{$alias}/templates/{$templateKey}");
    }
}
