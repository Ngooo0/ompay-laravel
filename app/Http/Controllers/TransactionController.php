<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Transaction;
use App\Services\ClientService;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Opérations financières et gestion du solde"
 * )
 */

class TransactionController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
    /**
     * Récupérer le solde actuel du client authentifié
     */
    public function balance(Request $request, $id)
    {
        $client = $request->user();
        // recharger depuis la base pour obtenir le solde à jour
        $client = $client->fresh();
        return response()->json(['balance' => $client->balance]);
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $transaction = $this->clientService->deposit($request->user(), (float)$data['amount'], $data['description'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'success', 'transaction' => $transaction], 201);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        try {
            $transaction = $this->clientService->withdraw($request->user(), (float)$data['amount'], $data['description'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'success', 'transaction' => $transaction], 201);
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'beneficiary_phone' => 'required|string',
            'description' => 'nullable|string',
        ]);

        try {
            $result = $this->clientService->transfer($request->user(), $data['beneficiary_phone'], (float)$data['amount'], $data['description'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'success', 'from' => $result['from'], 'to' => $result['to']], 201);
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'merchant_code' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $transaction = $this->clientService->payToMerchant($request->user(), $data['merchant_code'], (float)$data['amount']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'success', 'transaction' => $transaction], 201);
    }

    /**
     * Transfert vers un numéro de téléphone
     *
     * @OA\Post(
     *     path="/comptes/{id}/transfert",
     *     summary="Transfert d'argent vers un numéro de téléphone",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client expéditeur",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_phone","amount"},
     *             @OA\Property(property="recipient_phone", type="string", example="+221771234567"),
     *             @OA\Property(property="amount", type="integer", example=1000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="sender_phone", type="string", example="+221771234508"),
     *             @OA\Property(property="sender_name", type="string", example="Dupont Jean"),
     *             @OA\Property(property="recipient_phone", type="string", example="+221771234567"),
     *             @OA\Property(property="recipient_name", type="string", example="Martin Marie"),
     *             @OA\Property(property="amount", type="integer", example=-1000),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation ou solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Solde insuffisant")
     *         )
     *     )
     * )
     */
    public function transfert(Request $request)
    {
        $data = $request->validate([
            'recipient_phone' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->clientService->transferByPhone(
                $request->user(),
                $data['recipient_phone'],
                $data['amount']
            );

            return response()->json([
                'status' => 'success',
                'sender_phone' => $result['sender_phone'],
                'sender_name' => $result['sender_name'],
                'recipient_phone' => $result['recipient_phone'],
                'recipient_name' => $result['recipient_name'],
                'amount' => $result['amount'],
                'message' => $result['message']
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Paiement vers un numéro de téléphone ou code marchand
     *
     * @OA\Post(
     *     path="/comptes/{id}/paiement",
     *     summary="Paiement vers un numéro de téléphone ou code marchand",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client payeur",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient","amount"},
     *             @OA\Property(property="recipient", type="string", example="+221771234567", description="Numéro de téléphone ou code marchand"),
     *             @OA\Property(property="amount", type="integer", example=500)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="sender_phone", type="string", example="+221771234508"),
     *             @OA\Property(property="sender_name", type="string", example="Dupont Jean"),
     *             @OA\Property(property="recipient_phone", type="string", example="+221771234567"),
     *             @OA\Property(property="recipient_name", type="string", example="Martin Marie"),
     *             @OA\Property(property="amount", type="integer", example=-500),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation ou solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Solde insuffisant")
     *         )
     *     )
     * )
     */
    public function paiement(Request $request)
    {
        $data = $request->validate([
            'recipient' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->clientService->payByPhoneOrMerchant(
                $request->user(),
                $data['recipient'],
                $data['amount']
            );

            return response()->json([
                'status' => 'success',
                'sender_phone' => $result['sender_phone'],
                'sender_name' => $result['sender_name'],
                'recipient_phone' => $result['recipient_phone'],
                'recipient_name' => $result['recipient_name'],
                'amount' => $result['amount'],
                'message' => $result['message']
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Liste des transactions du client avec format détaillé
     *
     * @OA\Get(
     *     path="/comptes/transactions",
     *     summary="Liste des transactions du client",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
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
     *     )
     * )
     */
    public function list(Request $request)
    {
        $client = $request->user();
        $transactions = Transaction::with(['client', 'beneficiary'])
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

        return response()->json(['transactions' => $formattedTransactions]);
    }

    /**
     * Liste de toutes les transactions (administrateur)
     *
     * @OA\Get(
     *     path="/admin/transactions",
     *     summary="Liste de toutes les transactions (administrateur)",
     *     tags={"Administration"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste de toutes les transactions",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="type", type="string", example="transfert"),
     *                     @OA\Property(property="montant_envoye", type="integer", example=1000),
     *                     @OA\Property(property="numero_destinataire", type="string", example="+221771234567"),
     *                     @OA\Property(property="nom_destinataire", type="string", example="Martin Marie"),
     *                     @OA\Property(property="numero_expediteur", type="string", example="+221771234508"),
     *                     @OA\Property(property="nom_expediteur", type="string", example="Dupont Jean"),
     *                     @OA\Property(property="date", type="string", example="2025-11-18"),
     *                     @OA\Property(property="heure", type="string", example="14:30:25")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=150),
     *             @OA\Property(property="page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     )
     * )
     */
    public function getAllTransactions(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Réduit par défaut
        $page = $request->get('page', 1);

        $transactions = Transaction::orderByDesc('created_at')
            ->paginate($perPage);

        $formattedTransactions = $transactions->map(function ($transaction) {
            // Logique des montants : + pour dépôts, - pour autres transactions (déjà négatifs en base)
            $formattedAmount = $transaction->type === 'deposit' ?
                abs($transaction->amount) : // positif pour dépôts
                $transaction->amount;       // négatif pour retraits/paiements/transferts (déjà négatif en base)

            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'montant_envoye' => $formattedAmount,
                'numero_destinataire' => $transaction->client_phone,
                'nom_destinataire' => 'Marchand', // Simplifié pour l'admin
                'numero_expediteur' => 'Client ID: ' . $transaction->client_id,
                'nom_expediteur' => 'Client ID: ' . $transaction->client_id,
                'client_phone' => 'Client ID: ' . $transaction->client_id, // Ajouter numéro client
                'date' => $transaction->created_at->format('Y-m-d'),
                'heure' => $transaction->created_at->format('H:i:s'),
            ];
        });

        return response()->json([
            'transactions' => $formattedTransactions,
            'total' => $transactions->total(),
            'page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'last_page' => $transactions->lastPage(),
        ]);
    }
}
