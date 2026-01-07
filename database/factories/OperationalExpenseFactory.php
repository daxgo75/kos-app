<?php

namespace Database\Factories;

use App\Models\OperationalExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

class OperationalExpenseFactory extends Factory
{
    protected $model = OperationalExpense::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(100000, 5000000),
            'due_date' => $this->faker->dateTimeBetween('+0 days', '+30 days'),
            'notification_enabled' => true,
            'notification_time' => '09:00',
            'status' => 'pending',
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function overdue(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 days'),
        ]);
    }

    public function notificationDisabled(): self
    {
        return $this->state(fn (array $attributes) => [
            'notification_enabled' => false,
        ]);
    }
}
