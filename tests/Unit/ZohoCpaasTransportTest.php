<?php

namespace Olorunda\ZohoCpaas\Tests\Unit;

use Mockery;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\Mail\Headers\ZeptoMailHeader;
use Olorunda\ZohoCpaas\Services\EmailService;
use Olorunda\ZohoCpaas\Transport\ZohoCpaasTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ZohoCpaasTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_zoho_cpaas_transport_sends_email(): void
    {
        $clientMock = Mockery::mock(ZohoCpaasClientInterface::class);
        $emailServiceMock = Mockery::mock(EmailService::class);

        $clientMock->shouldReceive('emails')->andReturn($emailServiceMock);

        $emailServiceMock->shouldReceive('send')
            ->once()
            ->withArgs(function ($builder) {
                $this->assertInstanceOf(EmailMessage::class, $builder);
                $payload = $builder->toPayload();
                $this->assertSame('Hello ZeptoMail', $payload['subject']);
                $this->assertSame(['address' => 'sender@domain.com', 'name' => 'Sender'], $payload['from']);
                $this->assertSame([['email_address' => ['address' => 'recipient@domain.com', 'name' => 'Recipient']]], $payload['to']);
                $this->assertSame('<p>Test HTML body</p>', $payload['htmlbody']);
                $this->assertSame('Test text body', $payload['textbody']);
                $this->assertSame('REF-12345', $payload['client_reference']);
                return true;
            })
            ->andReturn([
                'data' => [
                    [
                        'code' => 'EM_104',
                        'message' => 'Email request processed successfully',
                        'details' => ['message_id' => 'msg-999'],
                    ],
                ],
            ]);

        $transport = new ZohoCpaasTransport($clientMock);

        $email = (new Email())
            ->from(new Address('sender@domain.com', 'Sender'))
            ->to(new Address('recipient@domain.com', 'Recipient'))
            ->subject('Hello ZeptoMail')
            ->html('<p>Test HTML body</p>')
            ->text('Test text body');

        $email->getHeaders()->addTextHeader(ZeptoMailHeader::CLIENT_REFERENCE, 'REF-12345');

        $envelope = new Envelope(new Address('sender@domain.com', 'Sender'), [new Address('recipient@domain.com', 'Recipient')]);

        $transport->send($email, $envelope);

        $this->assertSame('zoho-cpaas', (string) $transport);
    }
}
