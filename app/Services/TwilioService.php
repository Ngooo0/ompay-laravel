<?php

namespace App\Services;

class TwilioService
{
    public function sendOtpSms(string $phone, string $otpCode): bool
    {
        // For testing purposes, just return true
        // In production, implement actual Twilio SMS sending
        return true;
    }
}