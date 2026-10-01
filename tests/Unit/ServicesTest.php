<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use Mockery;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use Olorunda\ZohoCpaas\Services\AgentService;
use Olorunda\ZohoCpaas\Services\DomainService;
use Olorunda\ZohoCpaas\Services\EmailService;
use Olorunda\ZohoCpaas\Services\LogService;
use Olorunda\ZohoCpaas\Services\SuppressionService;
use Olorunda\ZohoCpaas\Services\TemplateService;
use PHPUnit\Framework\TestCase;

class ServicesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_email_service_dispatches_correct_methods(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $config = ['from' => ['address' => 'sender@domain.com']];
        $service = new EmailService($client, $config);

        $client->shouldReceive('sendMailApi')
            ->once()
            ->with('POST', '/email', Mockery::type('array'))
            ->andReturn(['status' => 'success']);

        $res1 = $service->send(EmailMessage::create()->to('rec@domain.com')->subject('Hi'));
        $this->assertSame('success', $res1['status']);

        $client->shouldReceive('sendMailApi')
            ->once()
            ->with('POST', '/email/batch', Mockery::type('array'))
            ->andReturn(['status' => 'success']);

        $res2 = $service->sendBatch(BatchEmailMessage::create()->to('rec@domain.com'));
        $this->assertSame('success', $res2['status']);

        $client->shouldReceive('sendMailApi')
            ->once()
            ->with('POST', '/email/template', Mockery::type('array'))
            ->andReturn(['status' => 'success']);

        $res3 = $service->sendTemplate(TemplateEmailMessage::create('key1')->to('rec@domain.com'));
        $this->assertSame('success', $res3['status']);

        $client->shouldReceive('sendMailApi')
            ->once()
            ->with('POST', '/email/template/batch', Mockery::type('array'))
            ->andReturn(['status' => 'success']);

        $res4 = $service->sendBatchTemplate(BatchTemplateEmailMessage::create('key1')->to('rec@domain.com'));
        $this->assertSame('success', $res4['status']);

        $client->shouldReceive('sendMailApi')
            ->once()
            ->with('POST', '/files', Mockery::subset(['query' => ['name' => 'doc.pdf']]))
            ->andReturn(['file_cache_key' => 'fc_123']);

        $res5 = $service->uploadFileCache('file content', 'doc.pdf');
        $this->assertSame('fc_123', $res5['file_cache_key']);
    }

    public function test_template_service_dispatches_oauth_requests(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $config = ['mailagent_alias' => 'default_agent'];
        $service = new TemplateService($client, $config);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/mailagents/default_agent/templates/tmpl_key')
            ->andReturn(['data' => ['template_key' => 'tmpl_key']]);

        $res = $service->get('tmpl_key');
        $this->assertSame('tmpl_key', $res['data']['template_key']);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/mailagents/default_agent/templates', Mockery::type('array'))
            ->andReturn(['data' => []]);

        $this->assertSame(['data' => []], $service->all());
    }

    public function test_agent_service_dispatches_oauth_requests(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $service = new AgentService($client, []);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/mailagents')
            ->andReturn(['data' => []]);

        $this->assertSame(['data' => []], $service->all());

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('POST', '/mailagents', ['json' => ['mailagent_name' => 'Agent1', 'description' => 'Test']])
            ->andReturn(['status' => 'success']);

        $this->assertSame('success', $service->create('Agent1', 'Test')['status']);
    }

    public function test_domain_service_dispatches_oauth_requests(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $service = new DomainService($client, []);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/domains')
            ->andReturn(['data' => []]);

        $this->assertSame(['data' => []], $service->all());

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('PUT', '/domains/dom_123/verify')
            ->andReturn(['status' => 'success']);

        $this->assertSame('success', $service->verify('dom_123')['status']);
    }

    public function test_suppression_service_dispatches_oauth_requests(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $service = new SuppressionService($client, []);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/suppressions/email_address', ['query' => []])
            ->andReturn(['data' => []]);

        $this->assertSame(['data' => []], $service->get('email_address'));
    }

    public function test_log_service_dispatches_oauth_requests(): void
    {
        $client = Mockery::mock(ZohoCpaasClientInterface::class);
        $service = new LogService($client, []);

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/email', ['query' => []])
            ->andReturn(['data' => []]);

        $this->assertSame(['data' => []], $service->all());

        $client->shouldReceive('oauthApi')
            ->once()
            ->with('GET', '/email/email-reference/ref-456')
            ->andReturn(['data' => ['reference' => 'ref-456']]);

        $this->assertSame('ref-456', $service->get('ref-456')['data']['reference']);
    }
}
