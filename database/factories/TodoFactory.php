<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'user_id' => User::factory(),

            'title' => fake()->sentence(),

            'description' => fake()->paragraph(),

            'status' => fake()->randomElement([
                0,
                1,
                2
            ]),

            'priority' => fake()->randomElement([
                0,
                1,
                2
            ]),

            'due_date' => fake()->optional()->dateTimeBetween(
                'now',
                '+30 days'
            ),

            'completed_at' => fake()->optional()->dateTimeBetween(
                '-30 days',
                'now'
            ),
        ];
    }
}
