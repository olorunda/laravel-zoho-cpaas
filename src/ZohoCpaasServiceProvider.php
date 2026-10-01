<?php

namespace Olorunda\ZohoCpaas;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;
use Olorunda\ZohoCpaas\Commands\ListAgentsCommand;
use Olorunda\ZohoCpaas\Commands\ListTemplatesCommand;
use Olorunda\ZohoCpaas\Commands\SendTestEmailCommand;
use Olorunda\ZohoCpaas\Contracts\TokenStoreInterface;
use Olorunda\ZohoCpaas\Contracts\ZohoCpaasClientInterface;
use Olorunda\ZohoCpaas\Storage\ArrayTokenStore;
use Olorunda\ZohoCpaas\Storage\CacheTokenStore;
use Olorunda\ZohoCpaas\Transport\ZohoCpaasTransport;

class ZohoCpaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/zoho-cpaas.php', 'zoho-cpaas');

        $this->app->singleton(TokenStoreInterface::class, function ($app) {
            $config = $app['config']->get('zoho-cpaas.oauth', []);
            $driver = $config['token_store'] ?? 'cache';
            $key = $config['cache_key'] ?? 'zoho_cpaas_access_token';

            if ($driver === 'array') {
                return new ArrayTokenStore();
            }

            return new CacheTokenStore($app['cache']->store(), $key);
        });

        $this->app->singleton('zoho-cpaas', function ($app) {
            return new ZohoCpaasManager($app);
        });

        $this->app->singleton(ZohoCpaasClientInterface::class, function ($app) {
            return $app['zoho-cpaas']->client();
        });

        $this->app->alias('zoho-cpaas', ZohoCpaasManager::class);
        $this->app->alias('zoho-cpaas', 'zeptomail');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/zoho-cpaas.php' => config_path('zoho-cpaas.php'),
            ], 'zoho-cpaas-config');

            $this->commands([
                SendTestEmailCommand::class,
                ListAgentsCommand::class,
                ListTemplatesCommand::class,
            ]);
        }

        // Register custom mail transport in Laravel MailManager
        $this->registerMailTransport();
    }

    protected function registerMailTransport(): void
    {
        $this->app->resolving('mail.manager', function (MailManager $mailManager) {
            $mailManager->extend('zoho-cpaas', function ($config = []) {
                return new ZohoCpaasTransport(
                    $this->app->make(ZohoCpaasClientInterface::class)
                );
            });

            $mailManager->extend('zeptomail', function ($config = []) {
                return new ZohoCpaasTransport(
                    $this->app->make(ZohoCpaasClientInterface::class)
                );
            });
        });
    }

    public function provides(): array
    {
        return [
            'zoho-cpaas',
            'zeptomail',
            ZohoCpaasManager::class,
            ZohoCpaasClientInterface::class,
            TokenStoreInterface::class,
        ];
    }
}
