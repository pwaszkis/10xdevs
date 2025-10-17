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
     * Disable Mailgun click tracking for all emails to prevent URL rewriting.
     * This ensures verification links and other URLs remain clean and direct.
     */
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $message = $event->message;

            // Only add Mailgun headers if using Mailgun mailer
            if (config('mail.default') === 'mailgun') {
                // Disable click tracking - prevents URLs from being rewritten
                // to url7497.przem-podroze.pl tracking links
                $message->getHeaders()->addTextHeader('o:tracking-clicks', 'no');

                // Optionally disable opens tracking as well (doesn't affect URLs but improves privacy)
                $message->getHeaders()->addTextHeader('o:tracking-opens', 'no');
            }
        });
    }
}
