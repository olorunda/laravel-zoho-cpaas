<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use InvalidArgumentException;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use PHPUnit\Framework\TestCase;

class TemplateEmailMessageTest extends TestCase
{
    public function test_template_email_message_builds_payload(): void
    {
        $msg = TemplateEmailMessage::create('template_key_456')
            ->mailagentAlias('primary_agent')
            ->from('support@domain.com', 'Support')
            ->to('customer@domain.com', 'Customer')
            ->mergeInfo(['plan' => 'Enterprise', 'code' => '998877']);

        $payload = $msg->toPayload();

        $this->assertSame('template_key_456', $payload['template_key']);
        $this->assertSame('primary_agent', $payload['mailagent_alias']);
        $this->assertSame(['address' => 'support@domain.com', 'name' => 'Support'], $payload['from']);
        $this->assertSame([['email_address' => ['address' => 'customer@domain.com', 'name' => 'Customer']]], $payload['to']);
        $this->assertSame(['plan' => 'Enterprise', 'code' => '998877'], $payload['merge_info']);
    }

    public function test_template_alias_can_be_used_instead_of_key(): void
    {
        $msg = TemplateEmailMessage::create(null, 'welcome_email')
            ->from('support@domain.com')
            ->to('customer@domain.com');

        $payload = $msg->toPayload();

        $this->assertSame('welcome_email', $payload['template_alias']);
        $this->assertArrayNotHasKey('template_key', $payload);
    }

    public function test_template_email_throws_if_no_template_specified(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $msg = TemplateEmailMessage::create()
            ->from('support@domain.com')
            ->to('customer@domain.com');
        $msg->toPayload();
    }
}
