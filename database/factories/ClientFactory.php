<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => '221' . $this->faker->unique()->numerify('77#######'),
            'password' => Hash::make('password'),
            'balance' => 0,
        ];
    }
}
