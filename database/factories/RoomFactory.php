<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $roomNumber = 1;

        return [
            'room_number' => 'Kamar ' . $roomNumber++,
            'room_type' => $this->faker->randomElement(['standard', 'deluxe']),
            'monthly_rate' => $this->faker->randomElement([1000000, 1200000, 1500000, 2000000]), // in IDR
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['available', 'occupied', 'maintenance']),
            'capacity' => $this->faker->randomElement([1, 2]),
        ];
    }

    /**
     * Indicate that the room is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    /**
     * Indicate that the room is occupied.
     */
    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'occupied',
        ]);
    }
}
