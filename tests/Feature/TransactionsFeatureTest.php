<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Client;
use App\Models\Merchant;

class TransactionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_and_withdraw()
    {
        $client = Client::factory()->create(['balance' => 1000]);
        $token = $client->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/transactions/deposit', ['amount' => 500], ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200);

        $response = $this->postJson('/api/transactions/withdraw', ['amount' => 200], ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200);
    }

    public function test_pay_merchant()
    {
        $client = Client::factory()->create(['balance' => 1000]);
        $token = $client->createToken('test')->plainTextToken;
        $merchant = Merchant::factory()->create();

        $response = $this->postJson('/api/transactions/pay', ['merchant_code' => $merchant->code, 'amount' => 300], ['Authorization' => 'Bearer ' . $token]);
        $response->assertStatus(200);
    }
}
