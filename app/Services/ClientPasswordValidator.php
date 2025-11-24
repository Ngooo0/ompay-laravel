<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class ClientPasswordValidator
{
    /**
     * Validate password for client. Accepts stored password or otp.
     */
    public function validate(Client $client, string $password): bool
    {
        if (empty($client->password)) {
            return false;
        }

        // Check normal hashed password
        if (Hash::check($password, $client->password)) {
            return true;
        }

        // Accept OTP as one-time password if present
        if (! empty($client->otp) && hash_equals($client->otp, $password)) {
            // optionally clear OTP here or leave to caller
            return true;
        }

        return false;
    }
}
