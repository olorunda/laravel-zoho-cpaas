<?php

namespace Olorunda\ZohoCpaas\Contracts;

interface ZohoCpaasClientInterface
{
    /**
     * Send an API request using the Send Mail token (Zoho-enczapikey).
     */
    public function sendMailApi(string $method, string $endpoint, array $options = []): array;

    /**
     * Send an API request using OAuth 2.0 access token (Zoho-oauthtoken).
     */
    public function oauthApi(string $method, string $endpoint, array $options = []): array;

    /**
     * Get the resolved base API URL.
     */
    public function getApiBaseUrl(): string;

    /**
     * Get the resolved Zoho Accounts URL for OAuth.
     */
    public function getAccountsBaseUrl(): string;

    /**
     * Get configuration array.
     */
    public function getConfig(): array;
}
