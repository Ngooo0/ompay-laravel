<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\LoginClientRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="OMPAY API",
 *     version="1.0.0",
 *     description="API pour OMPAY - Authentification OTP"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

class AuthController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Inscription d'un nouveau client
     *
     * @OA\Post(
     *     path="/auth/register",
     *     summary="Inscription d'un nouveau client",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","email","phone","password","password_confirmation"},
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
     *             @OA\Property(property="phone", type="string", example="+221771234567"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client enregistré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Client enregistré avec succès"),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nom", type="string", example="Dupont"),
     *                 @OA\Property(property="prenom", type="string", example="Jean"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(StoreClientRequest $request)
    {
        $data = $request->only(['nom', 'prenom', 'email', 'phone', 'password']);
        $client = $this->clientService->registerClient($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Client enregistré avec succès',
            'client' => new ClientResource($client)
        ], 201);
    }

    /**
     * Connexion avec téléphone et mot de passe
     *
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion et génération d'OTP",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","password"},
     *             @OA\Property(property="phone", type="string", example="+221771234567"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé par SMS"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="phone", type="string", example="+221771234567"),
     *                 @OA\Property(property="requires_otp", type="boolean", example=true),
     *                 @OA\Property(property="otp_code", type="string", example="123456", description="Code OTP pour les tests")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants incorrects",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Téléphone ou mot de passe incorrect.")
     *         )
     *     )
     * )
     */
    public function login(LoginClientRequest $request)
    {
        $credentials = $request->only('phone', 'password');

        $client = Client::where('telephone', $credentials['phone'])->first();
        if (!$client || !Hash::check($credentials['password'], $client->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Téléphone ou mot de passe incorrect.'
            ], 401);
        }

        $otpInfo = $this->clientService->generateAndSendOTP($credentials['phone']);

        return response()->json([
            'status' => 'success',
            'message' => $otpInfo['message'],
            'data' => [
                'phone' => $otpInfo['phone'],
                'requires_otp' => true,
                'otp_code' => $otpInfo['otp_code'], // Pour les tests
            ]
        ], 200);
    }

    /**
     * Vérification de l'OTP
     *
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     summary="Vérification du code OTP et génération du token d'accès",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","otp_code"},
     *             @OA\Property(property="phone", type="string", example="+221771234567"),
     *             @OA\Property(property="otp_code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Code OTP vérifié avec succès. Authentification complète."),
     *             @OA\Property(property="access_token", type="string", example="13|hSepqOobEjvu1oSDNO4jt88UXyPbb71APskS6Cbz9019a648"),
     *             @OA\Property(property="refresh_token", type="string", example="14|KacUO8gG0QFDAgjOscfKTOp0VDjbu6i627Abtau3059cd0bd")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré.")
     *         )
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $data = $request->only(['phone', 'otp_code']);
            $client = $this->clientService->verifyOTP($data['phone'], $data['otp_code']);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Code OTP invalide ou expiré.'
                ], 401);
            }

            // Marquer l'OTP comme utilisé
            $otp = \App\Models\Otp::where('phone', $data['phone'])
                                 ->where('otp_code', $data['otp_code'])
                                 ->first();
            if ($otp) {
                $otp->markAsUsed();
            }

            // Créer deux tokens séparés avec Sanctum
            $accessToken = $client->createToken('API Access Token');
            $refreshToken = $client->createToken('API Refresh Token');

            $tokens = [
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->plainTextToken, // Token séparé mais même format
                'token_type' => 'Bearer',
                'expires_in' => null,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Code OTP vérifié avec succès. Authentification complète.',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token']
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification OTP', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rafraîchir le token d'accès
     *
     * @OA\Post(
     *     path="/auth/refresh-token",
     *     summary="Rafraîchissement du token d'accès",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="old_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="11|abc123..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", nullable=true, example=null)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Utilisateur non authentifié.")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // Pour Sanctum, vérifier si l'utilisateur est authentifié
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        // Révoquer l'ancien token si spécifié
        if ($request->refresh_token) {
            $client->tokens()->where('id', $request->refresh_token)->delete();
        }

        // Créer un nouveau token
        $newToken = $client->createToken('API Token');

        return response()->json([
            'status' => 'success',
            'data' => [
                'access_token' => $newToken->plainTextToken,
                'refresh_token' => $newToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => null,
            ]
        ], 200);
    }

    /**
     * Récupérer les informations du compte client connecté
     *
     * @OA\Get(
     *     path="/comptes",
     *     summary="Informations du compte client avec QR code et transactions",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du compte client",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nom", type="string", example="Dupont"),
     *                     @OA\Property(property="prenom", type="string", example="Jean"),
     *                     @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                     @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                     @OA\Property(property="balance", type="integer", example=1000)
     *                 ),
     *                 @OA\Property(property="qr_code", type="string", example="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=CLIENT:15:Dupont.Jean"),
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uuid", type="string", example="uuid-123"),
     *                         @OA\Property(property="amount", type="integer", example=500),
     *                         @OA\Property(property="type", type="string", example="deposit"),
     *                         @OA\Property(property="description", type="string", example="Dépôt"),
     *                         @OA\Property(property="status", type="string", example="success"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function comptes(Request $request)
    {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        // Générer le code QR (URL simple pour QR code)
        $qrData = "CLIENT:{$client->id}:{$client->nom}.{$client->prenom}";
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

        // Récupérer les transactions du client
        $transactions = $client->transactions()
            ->orderBy('created_at', 'desc')
            ->take(10) // Limiter aux 10 dernières transactions
            ->get();

        return response()->json([
            'status' => 'success',
            'client' => [
                'id' => $client->id,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'balance' => $client->balance,
            ],
            'qr_code' => $qrCodeUrl,
            'recent_transactions' => $transactions->map(function ($transaction) use ($client) {
                // Logique des montants : + pour dépôts, - pour autres transactions (déjà négatifs en base)
                $formattedAmount = $transaction->type === 'deposit' ?
                    abs($transaction->amount) : // positif pour dépôts
                    $transaction->amount;       // négatif pour retraits/paiements/transferts (déjà négatif en base)

                return [
                    'id' => $transaction->id,
                    'uuid' => $transaction->uuid,
                    'amount' => $formattedAmount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'client_phone' => $client->telephone, // Ajouter numéro client
                    'created_at' => $transaction->created_at,
                ];
            })
        ], 200);
    }

    /**
     * Récupérer le solde d'un compte client
     *
     * @OA\Get(
     *     path="/comptes/{id}/balance",
     *     summary="Récupérer le solde d'un compte client",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="balance", type="integer", example=1500),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nom", type="string", example="Dupont"),
     *                 @OA\Property(property="prenom", type="string", example="Jean"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Client non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getBalance($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'error' => 'Client non trouvé'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'balance' => $client->balance,
            'client' => [
                'nom' => $client->nom,
                'prenom' => $client->prenom,
                'telephone' => $client->telephone,
            ]
        ], 200);
    }

    /**
     * Récupérer les transactions d'un compte client
     *
     * @OA\Get(
     *     path="/comptes/{id}/transaction",
     *     summary="Récupérer les transactions d'un compte client",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="type", type="string", example="transfert"),
     *                     @OA\Property(property="montant_envoye", type="integer", example=1000),
     *                     @OA\Property(property="numero_destinataire", type="string", example="+221771234567"),
     *                     @OA\Property(property="nom_destinataire", type="string", example="Martin Marie"),
     *                     @OA\Property(property="numero_expediteur", type="string", example="+221771234508"),
     *                     @OA\Property(property="date", type="string", example="2025-11-18"),
     *                     @OA\Property(property="heure", type="string", example="14:30:25")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Client non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getTransactions($id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'error' => 'Client non trouvé'
            ], 404);
        }

        $transactions = \App\Models\Transaction::with(['client', 'beneficiary'])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        $formattedTransactions = $transactions->map(function ($transaction) {
            // Logique des montants : + pour dépôts, - pour autres transactions (déjà négatifs en base)
            $formattedAmount = $transaction->type === 'deposit' ?
                abs($transaction->amount) : // positif pour dépôts
                $transaction->amount;       // négatif pour retraits/paiements/transferts (déjà négatif en base)

            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'montant_envoye' => $formattedAmount,
                'numero_destinataire' => $transaction->beneficiary ? $transaction->beneficiary->telephone : $transaction->client_phone,
                'nom_destinataire' => $transaction->beneficiary ?
                    ($transaction->beneficiary->nom . ' ' . $transaction->beneficiary->prenom) :
                    'Marchand',
                'numero_expediteur' => $transaction->client->telephone,
                'client_phone' => $transaction->client->telephone, // Ajouter numéro client
                'date' => $transaction->created_at->format('Y-m-d'),
                'heure' => $transaction->created_at->format('H:i:s'),
            ];
        });

        return response()->json(['transactions' => $formattedTransactions], 200);
    }

    /**
     * Récupérer les informations du client connecté
     *
     * @OA\Get(
     *     path="/user",
     *     summary="Informations du client connecté",
     *     tags={"Utilisateur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du client",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nom", type="string", example="Dupont"),
     *                 @OA\Property(property="prenom", type="string", example="Jean"),
     *                 @OA\Property(property="email", type="string", example="jean.dupont@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Modèle représentant un client",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="Dupont"),
 *     @OA\Property(property="prenom", type="string", example="Jean"),
 *     @OA\Property(property="name", type="string", example="Dupont Jean"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@example.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="balance", type="number", format="float", example=100.50),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */