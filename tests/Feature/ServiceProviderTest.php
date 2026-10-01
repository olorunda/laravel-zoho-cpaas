<?php

namespace Olorunda\ZohoCpaas\Tests\Feature;

use Illuminate\Mail\MailManager;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\Facades\ZeptoMail;
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\Tests\TestCase;
use Olorunda\ZohoCpaas\Transport\ZohoCpaasTransport;
use Olorunda\ZohoCpaas\ZohoCpaasManager;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_singletons_and_bindings(): void
    {
        $this->assertTrue($this->app->bound('zoho-cpaas'));
        $this->assertTrue($this->app->bound(ZohoCpaasManager::class));
        $this->assertTrue($this->app->bound(ZohoCpaasClientInterface::class));
        $this->assertTrue($this->app->bound(TokenStoreInterface::class));

        $manager = $this->app->make('zoho-cpaas');
        $this->assertInstanceOf(ZohoCpaasManager::class, $manager);

        $client = $this->app->make(ZohoCpaasClientInterface::class);
        $this->assertSame($manager->client(), $client);
    }

    public function test_facades_resolve_correctly(): void
    {
        $this->assertInstanceOf(ZohoCpaasManager::class, ZohoCpaas::getFacadeRoot());
        $this->assertInstanceOf(ZohoCpaasManager::class, ZeptoMail::getFacadeRoot());
    }

    public function test_mail_manager_registers_zoho_cpaas_and_zeptomail_drivers(): void
    {
        $mailManager = $this->app->make(MailManager::class);

        // Configure custom mailer in config
        $this->app['config']->set('mail.mailers.zoho', [
            'transport' => 'zoho-cpaas',
        ]);
        $this->app['config']->set('mail.mailers.zepto', [
            'transport' => 'zeptomail',
        ]);

        $mailerZoho = $mailManager->mailer('zoho');
        $this->assertInstanceOf(ZohoCpaasTransport::class, $mailerZoho->getSymfonyTransport());

        $mailerZepto = $mailManager->mailer('zepto');
        $this->assertInstanceOf(ZohoCpaasTransport::class, $mailerZepto->getSymfonyTransport());
    }
}
