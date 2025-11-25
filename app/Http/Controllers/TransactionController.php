<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $client = $request->user();
        $transaction = $this->clientService->deposit($client, $request->amount);

        return response()->json([
            'status' => 'success',
            'message' => 'Dépôt effectué avec succès',
            'transaction' => $transaction,
        ]);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $client = $request->user();
        $transaction = $this->clientService->withdraw($client, $request->amount);

        return response()->json([
            'status' => 'success',
            'message' => 'Retrait effectué avec succès',
            'transaction' => $transaction,
        ]);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'recipient_phone' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        $client = $request->user();
        $result = $this->clientService->transferByPhone($client, $request->recipient_phone, $request->amount);

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    public function payToMerchant(Request $request)
    {
        $request->validate([
            'merchant_code' => 'required|string',
            'amount' => 'required|integer|min:1',
        ]);

        $client = $request->user();
        $transaction = $this->clientService->payToMerchant($client, $request->merchant_code, $request->amount);

        return response()->json([
            'status' => 'success',
            'message' => 'Paiement effectué avec succès',
            'transaction' => $transaction,
        ]);
    }

    public function list(Request $request)
    {
        $client = $request->user();
        $transactions = $client->transactions()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions,
        ]);
    }

    public function getAllTransactions(Request $request)
    {
        // Admin only
        $transactions = \App\Models\Transaction::with(['client'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions,
        ]);
    }

    // Other methods as needed
    public function transfert(Request $request)
    {
        return $this->transfer($request);
    }

    public function paiement(Request $request)
    {
        return $this->payToMerchant($request);
    }
}