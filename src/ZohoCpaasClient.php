<?php

namespace Olorunda\ZohoCpaas;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasAuthenticationException;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasException;
use Olorunda\ZohoCpaas\Services\AgentService;
use Olorunda\ZohoCpaas\Services\DomainService;
use Olorunda\ZohoCpaas\Services\EmailService;
use Olorunda\ZohoCpaas\Services\LogService;
use Olorunda\ZohoCpaas\Services\OAuthService;
use Olorunda\ZohoCpaas\Services\SuppressionService;
use Olorunda\ZohoCpaas\Services\TemplateService;

class ZohoCpaasClient implements ZohoCpaasClientInterface
{
    protected array $config;
    protected GuzzleClient $http;
    protected TokenStoreInterface $tokenStore;
    protected ?OAuthService $oauthService = null;
    protected ?EmailService $emailService = null;
    protected ?TemplateService $templateService = null;
    protected ?SuppressionService $suppressionService = null;
    protected ?AgentService $agentService = null;
    protected ?DomainService $domainService = null;
    protected ?LogService $logService = null;

    /**
     * Regional base URLs for ZeptoMail API.
     */
    protected static array $apiRegionUrls = [
        'us' => 'https://api.zeptomail.com',
        'eu' => 'https://api.zeptomail.eu',
        'in' => 'https://api.zeptomail.in',
        'au' => 'https://api.zeptomail.com.au',
        'ca' => 'https://api.zeptomail.ca',
        'sa' => 'https://api.zeptomail.sa',
        'cn' => 'https://api.zeptomail.com.cn',
    ];

    /**
     * Regional base URLs for Zoho Accounts OAuth server.
     */
    protected static array $accountsRegionUrls = [
        'us' => 'https://accounts.zoho.com',
        'eu' => 'https://accounts.zoho.eu',
        'in' => 'https://accounts.zoho.in',
        'au' => 'https://accounts.zoho.com.au',
        'ca' => 'https://accounts.zoho.ca',
        'sa' => 'https://accounts.zoho.sa',
        'cn' => 'https://accounts.zoho.com.cn',
    ];

    public function __construct(array $config, TokenStoreInterface $tokenStore, ?GuzzleClient $http = null)
    {
        $this->config = $config;
        $this->tokenStore = $tokenStore;
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $config['http']['timeout'] ?? 30.0,
            'connect_timeout' => $config['http']['connect_timeout'] ?? 10.0,
        ]);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getTokenStore(): TokenStoreInterface
    {
        return $this->tokenStore;
    }

    public function getApiBaseUrl(): string
    {
        if (!empty($this->config['api_url'])) {
            return rtrim($this->config['api_url'], '/');
        }

        $region = strtolower($this->config['region'] ?? 'us');
        return self::$apiRegionUrls[$region] ?? self::$apiRegionUrls['us'];
    }

    public function getAccountsBaseUrl(): string
    {
        if (!empty($this->config['accounts_url'])) {
            return rtrim($this->config['accounts_url'], '/');
        }

        $region = strtolower($this->config['region'] ?? 'us');
        return self::$accountsRegionUrls[$region] ?? self::$accountsRegionUrls['us'];
    }

    public function getVersion(): string
    {
        return $this->config['version'] ?? 'v1.1';
    }

    /**
     * Build full URL for an endpoint.
     */
    public function buildUrl(string $endpoint): string
    {
        $endpoint = '/' . ltrim($endpoint, '/');

        // Check if endpoint already includes version prefix
        $version = $this->getVersion();
        if (!str_starts_with($endpoint, "/{$version}/") && $endpoint !== "/{$version}") {
            $endpoint = "/{$version}{$endpoint}";
        }

        return $this->getApiBaseUrl() . $endpoint;
    }

    /**
     * Send an API request using the Send Mail token (Zoho-enczapikey).
     */
    public function sendMailApi(string $method, string $endpoint, array $options = []): array
    {
        $token = $this->config['send_mail_token'] ?? '';
        if (empty($token)) {
            throw new ZohoCpaasAuthenticationException(
                'Zoho CPaaS Send Mail Token is missing. Set ZOHO_CPAAS_SEND_MAIL_TOKEN in your environment.'
            );
        }

        // Format header: Zoho-enczapikey <token>
        $authHeader = str_starts_with($token, 'Zoho-enczapikey ')
            ? $token
            : 'Zoho-enczapikey ' . $token;

        $options['headers'] = array_merge([
            'Authorization' => $authHeader,
            'Accept' => 'application/json',
        ], $options['headers'] ?? []);

        return $this->requestWithRetry($method, $this->buildUrl($endpoint), $options);
    }

    /**
     * Send an API request using OAuth 2.0 access token (Zoho-oauthtoken).
     */
    public function oauthApi(string $method, string $endpoint, array $options = []): array
    {
        $accessToken = $this->oauth()->getValidAccessToken();

        $authHeader = str_starts_with($accessToken, 'Zoho-oauthtoken ')
            ? $accessToken
            : 'Zoho-oauthtoken ' . $accessToken;

        $options['headers'] = array_merge([
            'Authorization' => $authHeader,
            'Accept' => 'application/json',
        ], $options['headers'] ?? []);

        try {
            return $this->requestWithRetry($method, $this->buildUrl($endpoint), $options);
        } catch (ZohoCpaasAuthenticationException $e) {
            // Token might have expired early; clear cache, refresh once and retry
            $this->oauth()->clearToken();
            $newAccessToken = $this->oauth()->getValidAccessToken();

            $options['headers']['Authorization'] = str_starts_with($newAccessToken, 'Zoho-oauthtoken ')
                ? $newAccessToken
                : 'Zoho-oauthtoken ' . $newAccessToken;

            return $this->requestWithRetry($method, $this->buildUrl($endpoint), $options);
        }
    }

    /**
     * Request with exponential retry backoff for transient network issues.
     */
    protected function requestWithRetry(string $method, string $url, array $options = []): array
    {
        $maxRetries = (int) ($this->config['http']['retries'] ?? 2);
        $delayMs = (int) ($this->config['http']['retry_delay_ms'] ?? 500);
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = $this->http->request($method, $url, $options);
                $statusCode = $response->getStatusCode();
                $contents = (string) $response->getBody();
                $decoded = json_decode($contents, true);

                if ($decoded === null && $contents !== '') {
                    // Non-JSON response
                    return ['raw' => $contents, 'status_code' => $statusCode];
                }

                $data = $decoded ?? [];

                // Check for Zoho logical failure inside 200 responses
                if (isset($data['status']) && $data['status'] === 'failure') {
                    throw ZohoCpaasException::fromResponse($statusCode, $data);
                }

                return $data;
            } catch (ClientException|ServerException $e) {
                $response = $e->getResponse();
                $statusCode = $response ? $response->getStatusCode() : 500;
                $body = $response ? (json_decode((string) $response->getBody(), true) ?? []) : [];

                // Rate limit (429) or Server error (5xx) might be transient
                if (($statusCode === 429 || $statusCode >= 500) && $attempt <= $maxRetries) {
                    usleep($delayMs * 1000 * $attempt);
                    continue;
                }

                throw ZohoCpaasException::fromResponse($statusCode, $body);
            } catch (GuzzleException $e) {
                if ($attempt <= $maxRetries) {
                    usleep($delayMs * 1000 * $attempt);
                    continue;
                }

                throw new ZohoCpaasException(
                    "Zoho CPaaS HTTP request failed: {$e->getMessage()}",
                    null,
                    0,
                    [],
                    $e
                );
            }
        }
    }

    // Service accessors
    public function emails(): EmailService
    {
        if ($this->emailService === null) {
            $this->emailService = new EmailService($this, $this->config);
        }
        return $this->emailService;
    }

    public function templates(): TemplateService
    {
        if ($this->templateService === null) {
            $this->templateService = new TemplateService($this, $this->config);
        }
        return $this->templateService;
    }

    public function suppression(): SuppressionService
    {
        if ($this->suppressionService === null) {
            $this->suppressionService = new SuppressionService($this, $this->config);
        }
        return $this->suppressionService;
    }

    public function agents(): AgentService
    {
        if ($this->agentService === null) {
            $this->agentService = new AgentService($this, $this->config);
        }
        return $this->agentService;
    }

    public function domains(): DomainService
    {
        if ($this->domainService === null) {
            $this->domainService = new DomainService($this, $this->config);
        }
        return $this->domainService;
    }

    public function logs(): LogService
    {
        if ($this->logService === null) {
            $this->logService = new LogService($this, $this->config);
        }
        return $this->logService;
    }

    public function oauth(): OAuthService
    {
        if ($this->oauthService === null) {
            $this->oauthService = new OAuthService($this, $this->tokenStore, $this->config);
        }
        return $this->oauthService;
    }
}
