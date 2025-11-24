<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/comptes', [AuthController::class, 'comptes']);
    Route::get('/comptes/{id}/balance', [AuthController::class, 'getBalance']);
    Route::get('/comptes/{id}/transaction', [AuthController::class, 'getTransactions']);

    // Routes de transactions (dans comptes)
    Route::get('/comptes/transactions', [TransactionController::class, 'list']);
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit']);
    Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
    Route::post('/transactions/pay', [TransactionController::class, 'payToMerchant']);
    Route::post('/comptes/{id}/transfert', [TransactionController::class, 'transfert']);
    Route::post('/comptes/{id}/paiement', [TransactionController::class, 'paiement']);
});

// Routes administrateur (à protéger avec un middleware admin)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'getAllTransactions']);
});

// Route factice pour éviter les erreurs de redirection
Route::get('/login', function () {
    return response()->json(['message' => 'Use POST /api/auth/login'], 405);
})->name('login');
