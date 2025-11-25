<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'M' . $this->faker->unique()->numberBetween(100, 999),
            'business_name' => $this->faker->company(),
            'owner_id' => Client::factory(),
            'merchant_code' => 'MC' . $this->faker->unique()->numberBetween(1000, 9999),
        ];
    }
}
