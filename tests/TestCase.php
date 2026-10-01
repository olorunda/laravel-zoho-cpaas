<?php

namespace Olorunda\ZohoCpaas\Tests;

use Olorunda\ZohoCpaas\Facades\ZeptoMail;
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\ZohoCpaasServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ZohoCpaasServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ZohoCpaas' => ZohoCpaas::class,
            'ZeptoMail' => ZeptoMail::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('zoho-cpaas.send_mail_token', 'ph-sample-token-123');
        $app['config']->set('zoho-cpaas.region', 'us');
        $app['config']->set('zoho-cpaas.mailagent_alias', 'sample_agent');
        $app['config']->set('zoho-cpaas.from', [
            'address' => 'sender@domain.com',
            'name' => 'Sample Sender',
        ]);
        $app['config']->set('zoho-cpaas.oauth.token_store', 'array');
    }
}
