<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Disable email tracking (click/opens) for all mailers to prevent URL rewriting.
     * This ensures verification links and other URLs remain clean and direct.
     *
     * Supports:
     * - Mailgun: Uses o:tracking-* headers
     * - SendGrid: Uses X-SMTPAPI header
     */
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $message = $event->message;
            $mailer = config('mail.default');

            // Disable tracking for Mailgun
            if ($mailer === 'mailgun') {
                // Disable click tracking - prevents URLs from being rewritten
                // to url7497.przem-podroze.pl tracking links
                $message->getHeaders()->addTextHeader('o:tracking-clicks', 'no');
                $message->getHeaders()->addTextHeader('o:tracking-opens', 'no');
            }

            // Disable tracking for SendGrid (via SMTP)
            if ($mailer === 'smtp' && str_contains(config('mail.mailers.smtp.host', ''), 'sendgrid')) {
                // SendGrid uses X-SMTPAPI header to control tracking
                $smtpapi = json_encode([
                    'filters' => [
                        'clicktrack' => ['settings' => ['enable' => 0]],
                        'opentrack' => ['settings' => ['enable' => 0]],
                    ],
                ]);
                $message->getHeaders()->addTextHeader('X-SMTPAPI', $smtpapi);
            }
        });
    }
}
