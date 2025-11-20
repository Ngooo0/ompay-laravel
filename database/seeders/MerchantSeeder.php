<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Merchant;
use App\Models\Client;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        if ($clients->isEmpty()) {
            return;
        }

        foreach (range(1, 3) as $i) {
            Merchant::create([
                'name' => 'Merchant ' . $i,
                'code' => 'M' . now()->format('Ymd') . $i . uniqid(),
                'owner_id' => $clients->random()->id,
            ]);
        }
    }
}
