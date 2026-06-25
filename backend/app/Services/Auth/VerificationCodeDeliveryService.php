<?php

// Commentaire d'intention: isole l'envoi ou la journalisation des codes de verification.

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class VerificationCodeDeliveryService
{
    public function send(string $type, string $target, string $code): void
    {
        $message = "Votre code de verification University Key est : {$code}. Il expire dans 15 minutes.";

        if ($type === 'email') {
            if (app()->environment('production') && config('mail.default') === 'log') {
                throw new RuntimeException('Le mailer de production ne peut pas etre configure sur log pour les OTP.');
            }

            Mail::raw($message, function ($mail) use ($target): void {
                $mail->to($target)->subject('Votre code de verification University Key');
            });

            return;
        }

        $webhookUrl = config('services.sms.webhook_url');

        if ($webhookUrl) {
            Http::acceptJson()
                ->timeout(10)
                ->withToken(config('services.sms.token'))
                ->post($webhookUrl, ['to' => $target, 'message' => $message])
                ->throw();

            return;
        }

        // Le code n'est jamais journalise. En production, une passerelle SMS est obligatoire.
        if (app()->environment('production')) {
            throw new RuntimeException('La passerelle SMS de verification n’est pas configuree.');
        }

        Log::warning('verification.sms.delivery_not_configured', ['target_suffix' => substr($target, -4)]);
    }
}
