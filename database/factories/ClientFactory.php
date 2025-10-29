<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'name' => fake()->name(),
            'phone_number' => fake()->phoneNumber(),
            'business_name' => fake()->company(),
            'address' => fake()->address(),
            'field_id' => fake()->numberBetween(1,6),
            'user_id' => 4,
        ];
    }
}
