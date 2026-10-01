<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasAuthenticationException;
use Olorunda\ZohoCpaas\Services\OAuthService;
use Olorunda\ZohoCpaas\Storage\ArrayTokenStore;
use PHPUnit\Framework\TestCase;

class OAuthServiceTest extends TestCase
{
    public function test_exchange_code_stores_token_and_returns_data(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'mocked-access-token-123',
                'refresh_token' => 'mocked-refresh-token-456',
                'expires_in' => 3600,
                'api_domain' => 'https://api.zeptomail.com',
                'token_type' => 'Bearer',
            ])),
        ]);

        $store = new ArrayTokenStore();
        $clientMock = $this->createMock(ZohoCpaasClientInterface::class);
        $clientMock->method('getAccountsBaseUrl')->willReturn('https://accounts.zoho.com');

        $service = new OAuthService($clientMock, $store, [
            'oauth' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
            ],
            'http' => ['timeout' => 5],
        ]);

        // Replace internal http client with mock
        $ref = new \ReflectionProperty($service, 'http');
        $ref->setAccessible(true);
        $ref->setValue($service, new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        $res = $service->exchangeCode('auth-code-789');

        $this->assertSame('mocked-access-token-123', $res['access_token']);
        $this->assertSame('mocked-access-token-123', $store->get());
    }

    public function test_refresh_token_obtains_new_access_token(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'new-refreshed-token',
                'expires_in' => 3600,
            ])),
        ]);

        $store = new ArrayTokenStore();
        $clientMock = $this->createMock(ZohoCpaasClientInterface::class);
        $clientMock->method('getAccountsBaseUrl')->willReturn('https://accounts.zoho.com');

        $service = new OAuthService($clientMock, $store, [
            'oauth' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'refresh_token' => 'existing-refresh-token',
            ],
        ]);

        $ref = new \ReflectionProperty($service, 'http');
        $ref->setAccessible(true);
        $ref->setValue($service, new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        $res = $service->refreshToken();

        $this->assertSame('new-refreshed-token', $res['access_token']);
        $this->assertSame('new-refreshed-token', $store->get());
    }

    public function test_get_valid_access_token_uses_cache_first(): void
    {
        $store = new ArrayTokenStore();
        $store->set('cached-valid-token', 1000);

        $clientMock = $this->createMock(ZohoCpaasClientInterface::class);
        $service = new OAuthService($clientMock, $store, [
            'oauth' => [],
        ]);

        $this->assertSame('cached-valid-token', $service->getValidAccessToken());
    }
}
