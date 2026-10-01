<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use InvalidArgumentException;
use Olorunda\ZohoCpaas\DTO\Attachment;
use Olorunda\ZohoCpaas\DTO\EmailAddress;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\InlineImage;
use PHPUnit\Framework\TestCase;

class EmailMessageTest extends TestCase
{
    public function test_email_address_validates_and_formats(): void
    {
        $addr = new EmailAddress('user@example.com', 'John Doe');
        $this->assertSame('user@example.com', $addr->getAddress());
        $this->assertSame('John Doe', $addr->getName());

        $this->assertSame([
            'address' => 'user@example.com',
            'name' => 'John Doe',
        ], $addr->toArray());

        $this->assertSame([
            'email_address' => [
                'address' => 'user@example.com',
                'name' => 'John Doe',
            ],
        ], $addr->toRecipientArray());
    }

    public function test_email_address_throws_on_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EmailAddress('invalid-email');
    }

    public function test_email_message_builds_complete_payload(): void
    {
        $msg = EmailMessage::create()
            ->from('sender@example.com', 'Sender')
            ->to('recipient@example.com', 'Recipient')
            ->cc('cc@example.com', 'CC')
            ->bcc('bcc@example.com', 'BCC')
            ->replyTo('reply@example.com', 'Reply')
            ->subject('Test Subject')
            ->html('<h1>Hello World</h1>')
            ->text('Hello World')
            ->attach(Attachment::fromData('PDF CONTENT', 'test.pdf', 'application/pdf'))
            ->embedImage(InlineImage::fromData('IMG BYTES', 'logo-cid', 'logo.png', 'image/png'), 'logo-cid')
            ->mergeInfo(['name' => 'John'])
            ->clientReference('REF-999')
            ->bounceAddress('bounce@domain.com')
            ->trackClicks(true)
            ->trackOpens(false)
            ->header('X-Custom-Header', 'custom-value');

        $payload = $msg->toPayload();

        $this->assertSame(['address' => 'sender@example.com', 'name' => 'Sender'], $payload['from']);
        $this->assertSame([['email_address' => ['address' => 'recipient@example.com', 'name' => 'Recipient']]], $payload['to']);
        $this->assertSame([['email_address' => ['address' => 'cc@example.com', 'name' => 'CC']]], $payload['cc']);
        $this->assertSame([['email_address' => ['address' => 'bcc@example.com', 'name' => 'BCC']]], $payload['bcc']);
        $this->assertSame([['address' => 'reply@example.com', 'name' => 'Reply']], $payload['reply_to']);
        $this->assertSame('Test Subject', $payload['subject']);
        $this->assertSame('<h1>Hello World</h1>', $payload['htmlbody']);
        $this->assertSame('Hello World', $payload['textbody']);
        $this->assertSame('bounce@domain.com', $payload['bounce_address']);
        $this->assertSame('REF-999', $payload['client_reference']);
        $this->assertTrue($payload['track_clicks']);
        $this->assertFalse($payload['track_opens']);
        $this->assertSame(['name' => 'John'], $payload['merge_info']);
        $this->assertSame('custom-value', $payload['mime_headers']['X-Custom-Header']);

        $this->assertCount(1, $payload['attachments']);
        $this->assertSame('test.pdf', $payload['attachments'][0]['name']);
        $this->assertSame(base64_encode('PDF CONTENT'), $payload['attachments'][0]['content']);

        $this->assertCount(1, $payload['inline_images']);
        $this->assertSame('logo-cid', $payload['inline_images'][0]['cid']);
    }

    public function test_email_message_uses_defaults_when_provided(): void
    {
        $msg = EmailMessage::create()
            ->to('recipient@example.com')
            ->subject('Subject')
            ->html('<p>Hi</p>');

        $defaults = [
            'from' => ['address' => 'default@domain.com', 'name' => 'Default Name'],
            'bounce_address' => 'default-bounce@domain.com',
            'tracking' => ['track_clicks' => true, 'track_opens' => true],
        ];

        $payload = $msg->toPayload($defaults);

        $this->assertSame(['address' => 'default@domain.com', 'name' => 'Default Name'], $payload['from']);
        $this->assertSame('default-bounce@domain.com', $payload['bounce_address']);
        $this->assertTrue($payload['track_clicks']);
        $this->assertTrue($payload['track_opens']);
    }

    public function test_email_message_requires_recipients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $msg = EmailMessage::create()->from('sender@example.com');
        $msg->toPayload();
    }
}
