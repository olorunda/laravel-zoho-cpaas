<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasAuthenticationException;
use Olorunda\ZohoCpaas\Exceptions\ZohoCpaasException;
use Olorunda\ZohoCpaas\Storage\ArrayTokenStore;
use Olorunda\ZohoCpaas\ZohoCpaasClient;
use PHPUnit\Framework\TestCase;

class ZohoCpaasClientTest extends TestCase
{
    public function test_resolves_regional_urls(): void
    {
        $store = new ArrayTokenStore();

        $clientUs = new ZohoCpaasClient(['region' => 'us'], $store);
        $this->assertSame('https://api.zeptomail.com', $clientUs->getApiBaseUrl());
        $this->assertSame('https://accounts.zoho.com', $clientUs->getAccountsBaseUrl());

        $clientEu = new ZohoCpaasClient(['region' => 'eu'], $store);
        $this->assertSame('https://api.zeptomail.eu', $clientEu->getApiBaseUrl());
        $this->assertSame('https://accounts.zoho.eu', $clientEu->getAccountsBaseUrl());

        $clientIn = new ZohoCpaasClient(['region' => 'in'], $store);
        $this->assertSame('https://api.zeptomail.in', $clientIn->getApiBaseUrl());
        $this->assertSame('https://accounts.zoho.in', $clientIn->getAccountsBaseUrl());

        $clientAu = new ZohoCpaasClient(['region' => 'au'], $store);
        $this->assertSame('https://api.zeptomail.com.au', $clientAu->getApiBaseUrl());
        $this->assertSame('https://accounts.zoho.com.au', $clientAu->getAccountsBaseUrl());

        $clientCustom = new ZohoCpaasClient([
            'api_url' => 'https://custom-api.example.com',
            'accounts_url' => 'https://custom-accounts.example.com',
        ], $store);
        $this->assertSame('https://custom-api.example.com', $clientCustom->getApiBaseUrl());
        $this->assertSame('https://custom-accounts.example.com', $clientCustom->getAccountsBaseUrl());
    }

    public function test_send_mail_api_adds_zoho_enczapikey_header(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    ['code' => 'EM_104', 'message' => 'Email request processed successfully'],
                ],
                'message' => 'success',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        $store = new ArrayTokenStore();
        $client = new ZohoCpaasClient([
            'send_mail_token' => 'my-secret-send-token',
        ], $store, $guzzle);

        $res = $client->sendMailApi('POST', '/email', [
            'json' => ['subject' => 'Hello'],
        ]);

        $this->assertSame('success', $res['message']);
        $this->assertSame('EM_104', $res['data'][0]['code']);

        $lastRequest = $mock->getLastRequest();
        $this->assertSame('Zoho-enczapikey my-secret-send-token', $lastRequest->getHeaderLine('Authorization'));
        $this->assertSame('https://api.zeptomail.com/v1.1/email', (string) $lastRequest->getUri());
    }

    public function test_throws_authentication_exception_when_token_is_missing(): void
    {
        $this->expectException(ZohoCpaasAuthenticationException::class);

        $store = new ArrayTokenStore();
        $client = new ZohoCpaasClient(['send_mail_token' => ''], $store);

        $client->sendMailApi('POST', '/email', ['json' => []]);
    }

    public function test_throws_exception_on_api_failure_response(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'error' => [
                    'code' => 'EM_101',
                    'message' => 'Invalid email address',
                ],
                'status' => 'failure',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        $store = new ArrayTokenStore();
        $client = new ZohoCpaasClient(['send_mail_token' => 'token'], $store, $guzzle);

        $this->expectException(ZohoCpaasException::class);
        $this->expectExceptionMessage('Invalid email address');

        $client->sendMailApi('POST', '/email', ['json' => []]);
    }
}
