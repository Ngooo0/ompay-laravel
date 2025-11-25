<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Client;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_flow()
    {
        $client = Client::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'phone' => $client->telephone,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
    }
}
