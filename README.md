# Laravel Zoho CPaaS & ZeptoMail Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/olorunda/laravel-zoho-cpaas.svg?style=flat-square)](https://packagist.org/packages/olorunda/laravel-zoho-cpaas)
[![Total Downloads](https://img.shields.io/packagist/dt/olorunda/laravel-zoho-cpaas.svg?style=flat-square)](https://packagist.org/packages/olorunda/laravel-zoho-cpaas)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A robust, enterprise-ready Laravel package for integrating with **Zoho CPaaS** and **Zoho ZeptoMail**.

This package provides a **native Laravel Mail transport driver** (`MAIL_MAILER=zoho-cpaas` or `MAIL_MAILER=zeptomail`) as well as a fluent API for transactional email sending, batch emails, templates, file cache uploads, suppression list (DND) management, mail agents, domains, email logs, and OAuth 2.0 token management.

---

## Features

- 🚀 **Native Laravel Mail Transport**: Send emails using `Mail::to(...)`, `Mailable`, and Notifications out-of-the-box.
- ⚡ **Fluent Email Builder**: Craft single, batch, and template emails with chainable methods.
- 📬 **Batch Email Personalization**: Send individual dynamic data (`merge_info`) per recipient in high-speed batches.
- 🖼 **Attachments & Inline Images**: Full support for file paths, raw data, base64 strings, and ZeptoMail File Cache keys.
- 🌐 **Multi-Region Data Centers**: Out-of-the-box support for US (`.com`), EU (`.eu`), India (`.in`), Australia (`.com.au`), Canada (`.ca`), Saudi Arabia (`.sa`), and China (`.com.cn`).
- 🔑 **Dual Authentication Engine**:
  - `Send Mail Token` (`Zoho-enczapikey`) for transactional email sending & file caching.
  - `OAuth 2.0` (`Zoho-oauthtoken`) with automated access token retrieval, token caching, and auto-refresh for administrative APIs.
- 🛠 **Full Management APIs**:
  - Email Templates (CRUD & Listing)
  - Suppression / DND Lists (Add, Update, Query, Delete)
  - Mail Agents & SMTP Passwords / API Keys
  - Verified Sending Domains (Add, Update, Verify, Delete)
  - Email Logs & Reference Search
- 🧰 **Artisan CLI Tools**: Test transactional mail delivery and inspect agents & templates straight from the command line.

---

## Requirements

- PHP `^8.1` or higher
- Laravel `^10.0`, `^11.0`, or `^12.0`
- GuzzleHTTP `^7.5`

---

## Installation

Install the package via Composer:

```bash
composer require olorunda/laravel-zoho-cpaas
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="zoho-cpaas-config"
```

This creates `config/zoho-cpaas.php`.

---

## Configuration

Add your Zoho CPaaS / ZeptoMail credentials to your `.env` file:

```env
# Mail Driver configuration (Use either 'zoho-cpaas' or 'zeptomail')
MAIL_MAILER=zoho-cpaas
MAIL_FROM_ADDRESS="notifications@yourverifieddomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# ZeptoMail Send Mail Token (Obtained under: Agent -> SMTP/API -> API tab)
ZOHO_CPAAS_SEND_MAIL_TOKEN="PHtE6xxxxxxxxxxxxxx"

# Zoho Region: 'us', 'eu', 'in', 'au', 'ca', 'sa', or 'cn' (Default: 'us')
ZOHO_CPAAS_REGION="us"

# Optional: Default Bounce Address
ZOHO_CPAAS_BOUNCE_ADDRESS="bounce@bounce.yourverifieddomain.com"

# Optional: Default Mail Agent Alias
ZOHO_CPAAS_MAILAGENT_ALIAS="primary_agent"

# Optional: OAuth 2.0 credentials (Required for Templates, Domains, Agents, Suppression & Logs)
ZOHO_CPAAS_CLIENT_ID="1000.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
ZOHO_CPAAS_CLIENT_SECRET="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
ZOHO_CPAAS_REFRESH_TOKEN="1000.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

### Laravel `config/mail.php`

In your Laravel `config/mail.php`, register the mailer under the `'mailers'` array:

```php
'mailers' => [
    // ...
    'zoho-cpaas' => [
        'transport' => 'zoho-cpaas',
    ],

    // Or 'zeptomail' alias:
    'zeptomail' => [
        'transport' => 'zeptomail',
    ],
],
```

---

## Usage

### 1. Using Standard Laravel Mail (`Mailable`)

When `MAIL_MAILER=zoho-cpaas` (or `zeptomail`), all standard Laravel `Mail::send()`, `Mailable` classes, and notifications work automatically:

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderShipped;

Mail::to('customer@example.com')->send(new OrderShipped($order));
```

#### Adding ZeptoMail-Specific Custom Headers to a Mailable

You can pass ZeptoMail template keys, client references, bounce addresses, or dynamic variables directly in your `Mailable`:

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Olorunda\ZohoCpaas\Mail\Headers\ZeptoMailHeader;

class WelcomeMailable extends Mailable
{
    use Queueable;

    public function headers(): Headers
    {
        return new Headers(
            text: [
                ZeptoMailHeader::CLIENT_REFERENCE => 'ORDER-98745',
                ZeptoMailHeader::TEMPLATE_ALIAS => 'welcome_email',
                ZeptoMailHeader::MERGE_INFO => json_encode([
                    'user_name' => 'Alice',
                    'login_url' => url('/login'),
                ]),
            ]
        );
    }

    public function build()
    {
        return $this->subject('Welcome!')
            ->html('<h1>Welcome aboard!</h1>');
    }
}
```

---

### 2. Using the Fluent Builder API (`ZohoCpaas` / `ZeptoMail` Facade)

The package provides two equivalent facades: `ZohoCpaas` and `ZeptoMail`.

#### Send Single Transactional Email

```php
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\DTO\EmailMessage;
use Olorunda\ZohoCpaas\DTO\Attachment;
use Olorunda\ZohoCpaas\DTO\InlineImage;

$response = ZohoCpaas::send(
    EmailMessage::create()
        ->to('recipient@example.com', 'Recipient Name')
        ->cc('billing@example.com')
        ->subject('Invoice #10023')
        ->html('<h1>Thank you for your purchase</h1><p>Find your receipt attached.</p>')
        ->text("Thank you for your purchase. Find your receipt attached.")
        ->attach(Attachment::fromPath(storage_path('app/invoice.pdf')))
        ->trackClicks(true)
        ->trackOpens(true)
        ->clientReference('INV-10023')
);
```

#### Send Batch Emails with Per-Recipient Variables (`merge_info`)

Send bulk transactional emails with custom personalized values for each recipient:

```php
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\DTO\BatchEmailMessage;

$response = ZohoCpaas::sendBatch(
    BatchEmailMessage::create()
        ->to('alice@example.com', 'Alice', [
            'name' => 'Alice',
            'order_id' => '1001',
            'amount' => '$45.00',
        ])
        ->to('bob@example.com', 'Bob', [
            'name' => 'Bob',
            'order_id' => '1002',
            'amount' => '$89.00',
        ])
        ->subject('Order Confirmation: {{order_id}}')
        ->html('<h2>Hello {{name}}</h2><p>Your order <b>{{order_id}}</b> totaling {{amount}} is confirmed.</p>')
);
```

#### Send Email Using Predefined ZeptoMail Template

```php
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\DTO\TemplateEmailMessage;

// Using Template Key:
$response = ZohoCpaas::sendTemplate(
    TemplateEmailMessage::create('2518b.4a719xxxxxxxxxxxx')
        ->to('user@example.com', 'User')
        ->mergeInfo([
            'otp' => '482910',
            'expires_in' => '10 minutes',
        ])
);

// Or using Template Alias:
$response = ZohoCpaas::sendTemplate(
    TemplateEmailMessage::create(null, 'password_reset_alias')
        ->to('user@example.com')
        ->mergeInfo(['reset_link' => 'https://example.com/reset?token=xyz'])
);
```

#### Send Batch Emails Using Template

```php
use Olorunda\ZohoCpaas\Facades\ZohoCpaas;
use Olorunda\ZohoCpaas\DTO\BatchTemplateEmailMessage;

$response = ZohoCpaas::sendBatchTemplate(
    BatchTemplateEmailMessage::create('template_key_here')
        ->to('user1@example.com', 'User 1', ['code' => 'ABC'])
        ->to('user2@example.com', 'User 2', ['code' => 'DEF'])
);
```

#### File Cache Upload

Upload large files once to ZeptoMail File Cache and reuse them across multiple emails via `file_cache_key`:

```php
$cacheResponse = ZohoCpaas::emails()->uploadFileCache(storage_path('app/large-catalog.pdf'), 'catalog.pdf');
$cacheKey = $cacheResponse['file_cache_key'];

// Attach using the cache key:
$email = EmailMessage::create()
    ->to('user@example.com')
    ->subject('Catalog')
    ->attachCache($cacheKey, 'catalog.pdf');

ZohoCpaas::send($email);
```

---

### 3. Management & Admin APIs (OAuth 2.0)

For administrative operations, ensure `ZOHO_CPAAS_CLIENT_ID`, `ZOHO_CPAAS_CLIENT_SECRET`, and `ZOHO_CPAAS_REFRESH_TOKEN` are set. Access tokens will be requested, cached, and refreshed automatically.

#### Email Templates

```php
// List all templates
$templates = ZohoCpaas::templates()->all('agent_alias');

// Get specific template
$template = ZohoCpaas::templates()->get('template_key', 'agent_alias');

// Create new template
$newTemplate = ZohoCpaas::templates()->create([
    'template_name' => 'Newsletter May',
    'subject' => 'May Updates',
    'htmlbody' => '<h1>Updates</h1>',
    'template_alias' => 'news_may',
], 'agent_alias');

// Update template
ZohoCpaas::templates()->update('template_key', [
    'template_name' => 'Updated Title',
    'subject' => 'New Subject',
    'htmlbody' => '<h1>New HTML</h1>',
], 'agent_alias');

// Delete template
ZohoCpaas::templates()->delete('template_key', 'agent_alias');
```

#### Suppression & DND (Do Not Disturb)

Manage bounces, unsubscribes, and spam blocks:

```php
// Get suppressed email addresses
$list = ZohoCpaas::suppression()->get('email_address');

// Add email to suppression list
ZohoCpaas::suppression()->add('email_address', ['spammer@example.com'], 'suppress');

// Delete from suppression list
ZohoCpaas::suppression()->delete('email_address', ['spammer@example.com']);
```

#### Mail Agents & SMTP / API Keys

```php
// List all mail agents
$agents = ZohoCpaas::agents()->all();

// Create new mail agent
$agent = ZohoCpaas::agents()->create('Marketing Agent', 'Agent for marketing blasts');

// Generate API key for an agent
$apiKey = ZohoCpaas::agents()->generateApiKey('mailagent_key');

// Generate SMTP short password
$smtpPassword = ZohoCpaas::agents()->generateShortPassword('mailagent_key');
```

#### Verified Sending Domains

```php
// List domains
$domains = ZohoCpaas::domains()->all();

// Add new sending domain
$domain = ZohoCpaas::domains()->create('mail.example.com', 'bounce-zem');

// Trigger DNS verification
ZohoCpaas::domains()->verify('domain_key');
```

#### Email Logs

```php
// Query recent outgoing logs
$logs = ZohoCpaas::logs()->all(['limit' => 50, 'offset' => 0]);

// Look up details for a specific email reference
$detail = ZohoCpaas::logs()->get('email_reference_id');
```

---

## Artisan Commands

### Send a Test Email

Quickly test your configuration and verify email deliverability:

```bash
php artisan zoho:test-mail recipient@example.com --name="John Doe" --subject="Test Email"
```

### List Mail Agents

```bash
php artisan zoho:list-agents
```

### List Email Templates

```bash
php artisan zoho:list-templates my_mailagent_alias
```

---

## Running Tests

Run the test suite with PHPUnit:

```bash
composer test
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
