<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use InvalidArgumentException;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;
use PHPUnit\Framework\TestCase;

class BatchEmailMessageTest extends TestCase
{
    public function test_batch_email_message_builds_payload(): void
    {
        $batch = BatchEmailMessage::create()
            ->from('sender@example.com', 'Sender')
            ->to('alice@example.com', 'Alice', ['name' => 'Alice', 'action_url' => 'https://example.com/1'])
            ->to('bob@example.com', 'Bob', ['name' => 'Bob', 'action_url' => 'https://example.com/2'])
            ->subject('Hello {{name}}')
            ->html('Click <a href="{{action_url}}">here</a>');

        $payload = $batch->toPayload();

        $this->assertCount(2, $payload['to']);
        $this->assertSame('alice@example.com', $payload['to'][0]['email_address']['address']);
        $this->assertSame('Alice', $payload['to'][0]['merge_info']['name']);
        $this->assertSame('bob@example.com', $payload['to'][1]['email_address']['address']);
        $this->assertSame('Bob', $payload['to'][1]['merge_info']['name']);
    }

    public function test_batch_template_email_message_builds_payload(): void
    {
        $batch = BatchTemplateEmailMessage::create('template-123')
            ->from('sender@example.com')
            ->to('alice@example.com', 'Alice', ['order_id' => '1001'])
            ->to('bob@example.com', 'Bob', ['order_id' => '1002']);

        $payload = $batch->toPayload();

        $this->assertSame('template-123', $payload['template_key']);
        $this->assertCount(2, $payload['to']);
        $this->assertSame('1001', $payload['to'][0]['merge_info']['order_id']);
    }

    public function test_batch_template_requires_template_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $batch = BatchTemplateEmailMessage::create()
            ->from('sender@example.com')
            ->to('alice@example.com');
        $batch->toPayload();
    }
}
