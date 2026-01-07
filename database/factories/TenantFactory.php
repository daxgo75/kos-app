<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'check_in_date' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'check_out_date' => null,
            'status' => 'active',
            'address' => $this->faker->address(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'check_out_date' => null,
        ]);
    }

    /**
     * Indicate that the tenant has moved out.
     */
    public function movedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'moved_out',
            'check_out_date' => $this->faker->dateTimeThisMonth(),
        ]);
    }
}
