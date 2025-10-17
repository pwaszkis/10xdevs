<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Potwierdź swój adres email - VibeTravels')
            ->greeting('Cześć!')
            ->line('Dziękujemy za rejestrację w VibeTravels!')
            ->line('Kliknij przycisk poniżej, aby potwierdzić swój adres email i rozpocząć planowanie niezapomnianych podróży.')
            ->action('Potwierdź adres email', $verificationUrl)
            ->line('Link jest ważny przez 60 minut.')
            ->line('Jeśli nie zakładałeś konta w VibeTravels, zignoruj tę wiadomość.')
            ->salutation('Miłych podróży, Zespół VibeTravels')
            // Disable Mailgun click tracking for verification links
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-Mailgun-Track-Clicks', 'no');
                $message->getHeaders()->addTextHeader('X-Mailgun-Track-Opens', 'no');
            });
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(mixed $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
