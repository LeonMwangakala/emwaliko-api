<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\CardClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'title' => fake()->optional()->jobTitle(),
            'card_class_id' => CardClass::inRandomOrder()->first()?->id ?? CardClass::factory(),
            'rsvp_status' => fake()->randomElement(['Yes', 'No', 'Maybe', 'Pending']),
        ];
    }
} 