<?php

namespace Olorunda\ZohoCpaas\Tests\Feature;

use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\Services\AgentService;
use Olorunda\ZohoCpaas\Services\TemplateService;
use Olorunda\ZohoCpaas\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_send_test_email_command_executes_successfully(): void
    {
        ZohoCpaas::shouldReceive('send')
            ->once()
            ->andReturn([
                'status' => 'success',
                'message' => 'Email request processed successfully',
            ]);

        $this->artisan('zoho:test-mail', [
            'email' => 'test@example.com',
            '--name' => 'Test User',
        ])
            ->expectsOutputToContain('Sending test email to <test@example.com>')
            ->expectsOutputToContain('Email sent successfully!')
            ->assertSuccessful();
    }

    public function test_list_agents_command_displays_table(): void
    {
        $agentService = $this->createMock(AgentService::class);
        $agentService->method('all')->willReturn([
            'data' => [
                [
                    'mailagent_name' => 'Agent One',
                    'mailagent_key' => 'key-111',
                    'status' => 'active',
                    'created_time' => '2026-01-01',
                    'description' => 'Test Agent',
                ],
            ],
        ]);

        ZohoCpaas::shouldReceive('agents')->once()->andReturn($agentService);

        $this->artisan('zoho:list-agents')
            ->expectsOutputToContain('Agent One')
            ->assertSuccessful();
    }

    public function test_list_templates_command_displays_table(): void
    {
        $templateService = $this->createMock(TemplateService::class);
        $templateService->method('all')->willReturn([
            'data' => [
                [
                    'template_name' => 'Welcome Email',
                    'template_key' => 'tmpl-001',
                    'template_alias' => 'welcome',
                    'subject' => 'Welcome to our service',
                    'created_time' => '2026-01-01',
                ],
            ],
        ]);

        ZohoCpaas::shouldReceive('templates')->once()->andReturn($templateService);

        $this->artisan('zoho:list-templates', ['agent' => 'sample_agent'])
            ->expectsOutputToContain('Welcome Email')
            ->assertSuccessful();
    }
}
