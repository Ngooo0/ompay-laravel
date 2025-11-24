<?php

namespace App\Services;

use App\Repository\ClientRepository;
use App\Models\Client;
use App\Models\Otp;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Utils\GenererUuid;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ClientService
{
    protected ClientRepository $repo;
    protected TwilioService $twilioService;

    public function __construct(ClientRepository $repo, TwilioService $twilioService)
    {
        $this->repo = $repo;
        $this->twilioService = $twilioService;
    }

    public function deposit(Client $client, int $amount): Transaction
    {
        $client->balance = (int) $client->balance + $amount;
        $client->save();

        return Transaction::create([
            'client_id' => $client->id,
            'amount' => $amount,
            'type' => 'deposit',
            'description' => 'Dépôt',
            'status' => 'success',
        ]);
    }

    public function withdraw(Client $client, int $amount): Transaction
    {
        if ((int) $client->balance < $amount) {
            throw new \Exception('Solde insuffisant');
        }

        $client->balance = (int) $client->balance - $amount;
        $client->save();

        return Transaction::create([
            'client_id' => $client->id,
            'amount' => -$amount,
            'type' => 'withdraw',
            'description' => 'Retrait',
            'status' => 'success',
        ]);
    }

    public function transfer(Client $from, Client $to, int $amount): Transaction
    {
        if ((int) $from->balance < $amount) {
            throw new \Exception('Solde insuffisant');
        }

        $from->balance = (int) $from->balance - $amount;
        $from->save();

        $to->balance = (int) $to->balance + $amount;
        $to->save();

        return Transaction::create([
            'client_id' => $from->id,
            'beneficiary_id' => $to->id,
            'amount' => -$amount,
            'type' => 'transfer',
            'description' => 'Virement vers ' . $to->id,
            'status' => 'success',
            'client_phone' => $to->telephone,
        ]);
    }

    public function payToMerchant(Client $client, string $merchantCode, int $amount): Transaction
    {
        $merchant = Merchant::where('code', $merchantCode)->first();
        if (! $merchant) {
            throw new \Exception('Merchant not found');
        }

        if ((int) $client->balance < $amount) {
            throw new \Exception('Solde insuffisant');
        }

        $client->balance = (int) $client->balance - $amount;
        $client->save();

        // Credit merchant owner if present
        if ($merchant->owner_id) {
            $owner = Client::find($merchant->owner_id);
            if ($owner) {
                $owner->balance = (int) $owner->balance + $amount;
                $owner->save();
            }
        }

        return Transaction::create([
            'client_id' => $client->id,
            'amount' => -$amount,
            'type' => 'payment',
            'description' => 'Paiement au commerçant ' . $merchantCode,
            'status' => 'success',
            'client_phone' => $merchantCode,
        ]);
    }

    /**
     * Enregistrer un nouveau client
     */
    public function registerClient(array $data): Client
    {
        // Normaliser le téléphone
        $data['telephone'] = $this->normalizePhone($data['phone'] ?? $data['telephone']);

        // Créer le nom complet
        $data['name'] = trim(($data['nom'] ?? '') . ' ' . ($data['prenom'] ?? ''));

        // Hash du mot de passe
        $data['password'] = bcrypt($data['password']);

        return Client::create([
            'nom' => $data['nom'] ?? '',
            'prenom' => $data['prenom'] ?? '',
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'password' => $data['password'],
        ]);
    }

    /**
     * Normaliser le numéro de téléphone
     */
    private function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($p) === 9 && $p[0] !== '+') {
            return '+221' . $p;
        }
        if (strlen($p) === 10 && $p[0] === '0') {
            return '+221' . substr($p, 1);
        }
        return $p;
    }

    /**
     * Générer et envoyer un OTP
     */
    public function generateAndSendOTP(string $phone): array
    {
        // Invalider les anciens OTP pour ce téléphone
        Otp::where('phone', $phone)->update(['used_at' => now()]);

        // Générer un nouveau code OTP
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Créer l'OTP en base
        $otp = Otp::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Envoyer par SMS
        $smsSent = $this->twilioService->sendOtpSms($phone, $otpCode);

        return [
            'otp_code' => $otpCode, // Pour les tests
            'phone' => $phone,
            'message' => $smsSent ? 'Code OTP envoyé par SMS' : 'Erreur envoi SMS, mais code généré',
            'expires_at' => $otp->expires_at,
        ];
    }

    /**
     * Vérifier l'OTP
     */
    public function verifyOTP(string $phone, string $otpCode): ?Client
    {
        $otp = Otp::where('phone', $phone)
                 ->where('otp_code', $otpCode)
                 ->valid()
                 ->first();

        if (!$otp) {
            return null;
        }

        // Retourner le client (l'OTP sera marqué comme utilisé dans le contrôleur après génération des tokens)
        return Client::where('telephone', $phone)->first();
    }

    /**
     * Transfert vers un numéro de téléphone
     */
    public function transferByPhone(Client $from, string $recipientPhone, int $amount): array
    {
        // Normaliser le numéro du destinataire
        $normalizedPhone = $this->normalizePhone($recipientPhone);

        // Vérifier que le destinataire existe
        $to = Client::where('telephone', $normalizedPhone)->first();
        if (!$to) {
            throw new \Exception('Destinataire non trouvé');
        }

        // Vérifier que l'expéditeur n'envoie pas à lui-même
        if ($from->id === $to->id) {
            throw new \Exception('Impossible de transférer à soi-même');
        }

        // Vérifier le solde
        if ((int) $from->balance < $amount) {
            throw new \Exception('Solde insuffisant');
        }

        // Effectuer le transfert
        $from->balance = (int) $from->balance - $amount;
        $from->save();

        $to->balance = (int) $to->balance + $amount;
        $to->save();

        // Créer la transaction
        Transaction::create([
            'client_id' => $from->id,
            'beneficiary_id' => $to->id,
            'amount' => -$amount,
            'type' => 'transfer',
            'description' => 'Transfert vers ' . $to->nom . ' ' . $to->prenom,
            'status' => 'success',
            'client_phone' => $normalizedPhone,
        ]);

        return [
            'sender_phone' => $from->telephone,
            'sender_name' => $from->nom . ' ' . $from->prenom,
            'recipient_phone' => $to->telephone,
            'recipient_name' => $to->nom . ' ' . $to->prenom,
            'amount' => -$amount,
            'message' => 'Transfert effectué avec succès'
        ];
    }

    /**
     * Paiement vers un numéro de téléphone ou code marchand
     */
    public function payByPhoneOrMerchant(Client $from, string $recipient, int $amount): array
    {
        $recipientType = 'phone';
        $to = null;
        $merchant = null;

        // Essayer d'abord comme numéro de téléphone
        $normalizedPhone = $this->normalizePhone($recipient);
        $to = Client::where('telephone', $normalizedPhone)->first();

        if ($to) {
            // C'est un paiement vers un client
            $recipientType = 'phone';
        } else {
            // Essayer comme code marchand
            $merchant = Merchant::where('merchant_code', $recipient)->first();
            if (!$merchant) {
                throw new \Exception('Destinataire non trouvé (ni numéro de téléphone ni code marchand valide)');
            }
            $recipientType = 'merchant';
        }

        // Vérifier le solde
        if ((int) $from->balance < $amount) {
            throw new \Exception('Solde insuffisant');
        }

        if ($recipientType === 'phone') {
            // Vérifier que l'expéditeur n'envoie pas à lui-même
            if ($from->id === $to->id) {
                throw new \Exception('Impossible de payer à soi-même');
            }

            // Effectuer le paiement vers un client
            $from->balance = (int) $from->balance - $amount;
            $from->save();

            $to->balance = (int) $to->balance + $amount;
            $to->save();

            // Créer la transaction
            Transaction::create([
                'uuid' => GenererUuid::uuid(),
                'client_id' => $from->id,
                'amount' => -$amount,
                'type' => 'payment',
                'description' => 'Paiement vers ' . $to->nom . ' ' . $to->prenom,
                'status' => 'success',
                'metadata' => [
                    'recipient_phone' => $normalizedPhone,
                    'recipient_name' => $to->nom . ' ' . $to->prenom
                ],
            ]);

            return [
                'sender_phone' => $from->telephone,
                'sender_name' => $from->nom . ' ' . $from->prenom,
                'recipient_phone' => $to->telephone,
                'recipient_name' => $to->nom . ' ' . $to->prenom,
                'amount' => -$amount,
                'message' => 'Paiement effectué avec succès'
            ];
        } else {
            // Paiement vers un marchand
            $from->balance = (int) $from->balance - $amount;
            $from->save();

            // Créditer le propriétaire du marchand si présent
            if ($merchant->owner_id) {
                $owner = Client::find($merchant->owner_id);
                if ($owner) {
                    $owner->balance = (int) $owner->balance + $amount;
                    $owner->save();
                }
            }

            // Créer la transaction
            Transaction::create([
                'uuid' => GenererUuid::uuid(),
                'client_id' => $from->id,
                'amount' => -$amount,
                'type' => 'payment',
                'description' => 'Paiement au commerçant ' . $merchant->business_name,
                'status' => 'success',
                'metadata' => [
                    'merchant_code' => $merchant->merchant_code,
                    'merchant_name' => $merchant->business_name
                ],
            ]);

            return [
                'sender_phone' => $from->telephone,
                'sender_name' => $from->nom . ' ' . $from->prenom,
                'recipient_phone' => $merchant->merchant_code,
                'recipient_name' => $merchant->business_name,
                'amount' => -$amount,
                'message' => 'Paiement effectué avec succès'
            ];
        }
    }
}
