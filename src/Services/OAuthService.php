<?php

namespace Olorunda\ZohoCpaas\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasAuthenticationException;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasException;

class OAuthService
{
    protected ZohoCpaasClientInterface $client;
    protected TokenStoreInterface $tokenStore;
    protected array $config;
    protected GuzzleClient $http;

    public function __construct(ZohoCpaasClientInterface $client, TokenStoreInterface $tokenStore, array $config)
    {
        $this->client = $client;
        $this->tokenStore = $tokenStore;
        $this->config = $config;
        $this->http = new GuzzleClient([
            'timeout' => $config['http']['timeout'] ?? 30.0,
            'connect_timeout' => $config['http']['connect_timeout'] ?? 10.0,
        ]);
    }

    /**
     * Get a valid access token. Uses token cache or refreshes automatically.
     */
    public function getValidAccessToken(): string
    {
        // 1. Check if tokenStore has a cached token
        $cachedToken = $this->tokenStore->get();
        if ($cachedToken !== null && $cachedToken !== '') {
            return $cachedToken;
        }

        // 2. Check if static access_token is provided in config
        $staticToken = $this->config['oauth']['access_token'] ?? null;
        $refreshToken = $this->config['oauth']['refresh_token'] ?? null;

        // If refresh token exists, refresh automatically to avoid expired static tokens
        if (!empty($refreshToken)) {
            $response = $this->refreshToken($refreshToken);
            if (!empty($response['access_token'])) {
                return $response['access_token'];
            }
        }

        if (!empty($staticToken)) {
            return $staticToken;
        }

        throw new ZohoCpaasAuthenticationException(
            'No valid Zoho CPaaS OAuth access token or refresh token is configured.'
        );
    }

    /**
     * Exchange authorization code for access and refresh tokens.
     */
    public function exchangeCode(string $code, ?string $redirectUri = null): array
    {
        $accountsUrl = $this->client->getAccountsBaseUrl();
        $clientId = $this->config['oauth']['client_id'] ?? '';
        $clientSecret = $this->config['oauth']['client_secret'] ?? '';
        $uri = $redirectUri ?? $this->config['oauth']['redirect_uri'] ?? null;

        if (empty($clientId) || empty($clientSecret)) {
            throw new ZohoCpaasAuthenticationException('Zoho OAuth client_id and client_secret are required.');
        }

        $params = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
        ];

        if ($uri !== null) {
            $params['redirect_uri'] = $uri;
        }

        try {
            $res = $this->http->post("{$accountsUrl}/oauth/v2/token", [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $body = json_decode((string) $res->getBody(), true) ?? [];

            if (isset($body['error'])) {
                throw new ZohoCpaasAuthenticationException(
                    $body['error_description'] ?? $body['error'],
                    $body['error'],
                    $res->getStatusCode()
                );
            }

            if (!empty($body['access_token'])) {
                $expiresIn = (int) ($body['expires_in'] ?? 3600);
                // Save in store with safety margin of 60 seconds
                $this->tokenStore->set($body['access_token'], max(60, $expiresIn - 60));
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new ZohoCpaasException("Failed to exchange Zoho OAuth code: {$e->getMessage()}", null, 0, [], $e);
        }
    }

    /**
     * Refresh OAuth access token using refresh_token.
     */
    public function refreshToken(?string $refreshToken = null): array
    {
        $accountsUrl = $this->client->getAccountsBaseUrl();
        $token = $refreshToken ?? $this->config['oauth']['refresh_token'] ?? null;
        $clientId = $this->config['oauth']['client_id'] ?? '';
        $clientSecret = $this->config['oauth']['client_secret'] ?? '';

        if (empty($token)) {
            throw new ZohoCpaasAuthenticationException('Zoho OAuth refresh_token is required to refresh access token.');
        }

        if (empty($clientId) || empty($clientSecret)) {
            throw new ZohoCpaasAuthenticationException('Zoho OAuth client_id and client_secret are required.');
        }

        try {
            $res = $this->http->post("{$accountsUrl}/oauth/v2/token", [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $body = json_decode((string) $res->getBody(), true) ?? [];

            if (isset($body['error'])) {
                throw new ZohoCpaasAuthenticationException(
                    $body['error_description'] ?? $body['error'],
                    $body['error'],
                    $res->getStatusCode()
                );
            }

            if (!empty($body['access_token'])) {
                $expiresIn = (int) ($body['expires_in'] ?? 3600);
                $this->tokenStore->set($body['access_token'], max(60, $expiresIn - 60));
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new ZohoCpaasException("Failed to refresh Zoho OAuth token: {$e->getMessage()}", null, 0, [], $e);
        }
    }

    /**
     * Clear cached access token.
     */
    public function clearToken(): void
    {
        $this->tokenStore->forget();
    }
}
