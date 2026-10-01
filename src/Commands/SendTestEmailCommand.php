<?php

namespace Olorunda\ZohoCpaas\Commands;

use Illuminate\Console\Command;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Throwable;

class SendTestEmailCommand extends Command
{
    protected $signature = 'zoho:test-mail
                            {email : Recipient email address}
                            {--name= : Recipient name}
                            {--subject=Test Email from Zoho CPaaS / ZeptoMail : Subject line}';

    protected $description = 'Send a test transactional email via Zoho CPaaS / ZeptoMail';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name');
        $subject = $this->option('subject');

        $this->info("Sending test email to <{$email}>...");

        try {
            $msg = EmailMessage::create()
                ->to($email, $name)
                ->subject($subject)
                ->html('<h1>Zoho CPaaS Test Email</h1><p>Congratulations! Your Laravel application is successfully integrated with <strong>Zoho CPaaS (ZeptoMail)</strong>.</p>')
                ->text("Zoho CPaaS Test Email\n\nCongratulations! Your Laravel application is successfully integrated with Zoho CPaaS (ZeptoMail).");

            $response = ZohoCpaas::send($msg);

            $this->info('Email sent successfully!');
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to send test email: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
