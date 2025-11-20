<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected ?Client $twilio;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (empty($sid) || empty($token)) {
            $this->twilio = null;
            return;
        }

        try {
            $this->twilio = new Client($sid, $token);
        } catch (\Exception $e) {
            $this->twilio = null;
        }
    }

    /**
     * Envoyer un SMS avec le code OTP
     */
    public function sendOtpSms(string $phone, string $otpCode): bool
    {
        if (!$this->twilio) {
            Log::warning("Twilio non configuré, OTP généré mais non envoyé: {$otpCode} à {$phone}");
            return false;
        }

        try {
            $message = "Votre code de vérification est : {$otpCode}";

            $this->twilio->messages->create(
                $phone,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message,
                ]
            );

            Log::info("OTP envoyé à {$phone}: {$otpCode}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS Twilio: " . $e->getMessage());
            return false;
        }
    }
}