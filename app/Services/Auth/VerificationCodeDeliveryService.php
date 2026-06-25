<?php

// Commentaire d'intention: isole l'envoi ou la journalisation des codes de verification.

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class VerificationCodeDeliveryService
{
    /**
     * Envoie un code OTP sans bloquer l'inscription quand le fournisseur
     * d'e-mail/SMS n'est pas encore branche dans l'environnement Cloud.
     *
     * @return array{delivered: bool, channel: string, reason?: string}
     */
    public function send(string $type, string $target, string $code): array
    {
        $message = "Votre code de verification University Key est : {$code}. Il expire dans 15 minutes.";

        if ($type === 'email') {
            if ($this->isStrict() && app()->environment('production') && config('mail.default') === 'log') {
                throw new RuntimeException('Le mailer de production ne peut pas etre configure sur log pour les OTP.');
            }

            try {
                Mail::raw($message, function ($mail) use ($target): void {
                    $mail->to($target)->subject('Votre code de verification University Key');
                });

                return ['delivered' => true, 'channel' => 'email'];
            } catch (Throwable $exception) {
                return $this->deliveryFailure('email', $target, 'email_delivery_failed', $exception);
            }
        }

        $webhookUrl = config('services.sms.webhook_url');

        if ($webhookUrl) {
            try {
                Http::acceptJson()
                    ->timeout(10)
                    ->withToken(config('services.sms.token'))
                    ->post($webhookUrl, ['to' => $target, 'message' => $message])
                    ->throw();

                return ['delivered' => true, 'channel' => 'telephone'];
            } catch (Throwable $exception) {
                return $this->deliveryFailure('telephone', $target, 'sms_delivery_failed', $exception);
            }
        }

        if ($this->isStrict() && app()->environment('production')) {
            throw new RuntimeException('La passerelle SMS de verification n\'est pas configuree.');
        }

        Log::warning('verification.sms.delivery_not_configured', ['target_suffix' => substr($target, -4)]);

        return ['delivered' => false, 'channel' => 'telephone', 'reason' => 'sms_not_configured'];
    }

    private function isStrict(): bool
    {
        return (bool) config('services.verification.strict_delivery');
    }

    /**
     * @return array{delivered: false, channel: string, reason: string}
     */
    private function deliveryFailure(string $channel, string $target, string $reason, Throwable $exception): array
    {
        if ($this->isStrict()) {
            throw $exception;
        }

        Log::warning('verification.delivery_failed', [
            'channel' => $channel,
            'target_suffix' => substr($target, -4),
            'reason' => $reason,
            'error' => $exception->getMessage(),
        ]);

        return ['delivered' => false, 'channel' => $channel, 'reason' => $reason];
    }
}
