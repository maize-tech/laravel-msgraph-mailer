# Laravel Microsoft Graph Mail

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maize-tech/laravel-msgraph-mail.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-msgraph-mail)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-msgraph-mail/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/maize-tech/laravel-msgraph-mail/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-msgraph-mail/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/maize-tech/laravel-msgraph-mail/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/maize-tech/laravel-msgraph-mail.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-msgraph-mail)

A Laravel mail transport driver for sending emails via Microsoft Graph API using OAuth2 authentication.

This package provides a custom Symfony Mailer transport that integrates seamlessly with Laravel's mail system, allowing you to send emails through Microsoft 365 (formerly Office 365) using the Microsoft Graph API with application permissions (client credentials flow).

## Installation

You can install the package via composer:

```bash
composer require maize-tech/laravel-msgraph-mail
```

## Configuration

Add the Microsoft Graph mailer configuration to your `config/mail.php` file:

```php
'mailers' => [
    // ... other mailers

    'microsoft-graph' => [
        'transport' => 'microsoft-graph',
        'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID'),
        'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
    ],
],
```

Add the required environment variables to your `.env` file:

```env
MAIL_MAILER=microsoft-graph

MICROSOFT_GRAPH_TENANT_ID=your-tenant-id
MICROSOFT_GRAPH_CLIENT_ID=your-client-id
MICROSOFT_GRAPH_CLIENT_SECRET=your-client-secret
```

## Usage

Once configured, you can use Laravel's Mail facade as usual. The package will automatically handle OAuth2 authentication and send emails via Microsoft Graph API.

### Basic Email

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Hello from Microsoft Graph!', function ($message) {
    $message->to('recipient@example.com')
            ->subject('Test Email');
});
```

### Using Mailables

```php
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

Mail::to('user@example.com')->send(new WelcomeEmail($user));
```

### HTML Emails with Attachments

```php
use Illuminate\Support\Facades\Mail;

Mail::send('emails.welcome', ['user' => $user], function ($message) use ($user) {
    $message->to($user->email)
            ->subject('Welcome!')
            ->attach('/path/to/file.pdf');
});
```

## Token Caching Issues

Access tokens are cached for 55 minutes by default. If you need to clear the cache:

```php
app('mail.microsoft-graph.token-provider')->clearToken();
```

## Features

- **OAuth2 Authentication**: Uses client credentials flow for secure authentication
- **Automatic Token Caching**: Access tokens are cached for 55 minutes
- **Retry Logic**: Automatic retry on transient failures with attempts and delays
- **Full Email Support**:
  - HTML and plain text emails
  - File attachments (including inline images)
  - Multiple recipients (To, CC, BCC)
  - Reply-To headers
- **Laravel Integration**: Works seamlessly with Laravel's Mail facade, Mailables, and Notifications

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Enrico De Lazzari](https://github.com/enricodelazzari)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
