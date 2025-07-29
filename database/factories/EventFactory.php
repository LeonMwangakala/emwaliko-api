<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\EventType;
use App\Models\CardType;
use App\Models\CardClass;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_name' => fake()->sentence(3),
            'customer_id' => Customer::factory(),
            'event_type_id' => EventType::inRandomOrder()->first()?->id ?? EventType::factory(),
            'card_type_id' => CardType::inRandomOrder()->first()?->id ?? CardType::factory(),
            'card_class_id' => CardClass::inRandomOrder()->first()?->id ?? CardClass::factory(),
            'package_id' => Package::inRandomOrder()->first()?->id ?? Package::factory(),
            'event_location' => fake()->address(),
            'event_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'notification_date' => fake()->dateTimeBetween('now', '+1 month'),
            'card_design_path' => fake()->optional()->filePath(),
        ];
    }
} 