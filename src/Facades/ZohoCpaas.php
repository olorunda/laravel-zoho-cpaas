<?php

namespace Olorunda\ZohoCpaas\Facades;

use Illuminate\Support\Facades\Facade;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;
use Olorunda\ZohoCpaas\Services\AgentService;
use Olorunda\ZohoCpaas\Services\DomainService;
use Olorunda\ZohoCpaas\Services\EmailService;
use Olorunda\ZohoCpaas\Services\LogService;
use Olorunda\ZohoCpaas\Services\OAuthService;
use Olorunda\ZohoCpaas\Services\SuppressionService;
use Olorunda\ZohoCpaas\Services\TemplateService;
use Olorunda\ZohoCpaas\ZohoCpaasClient;

/**
 * @method static array send(EmailMessage|array $message)
 * @method static array sendBatch(BatchEmailMessage|array $batchMessage)
 * @method static array sendTemplate(TemplateEmailMessage|array $templateMessage)
 * @method static array sendBatchTemplate(BatchTemplateEmailMessage|array $batchTemplateMessage)
 * @method static EmailService emails()
 * @method static TemplateService templates()
 * @method static SuppressionService suppression()
 * @method static AgentService agents()
 * @method static DomainService domains()
 * @method static LogService logs()
 * @method static OAuthService oauth()
 * @method static ZohoCpaasClient client()
 *
 * @see \Olorunda\ZohoCpaas\ZohoCpaasManager
 */
class ZohoCpaas extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'zoho-cpaas';
    }
}
