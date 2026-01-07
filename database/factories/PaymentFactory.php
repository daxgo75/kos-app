<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::inRandomOrder()->first() ?? Tenant::factory()->create();
        $amount_due = $tenant->room->monthly_rate;
        $amount_paid = 0;
        $remaining_amount = $amount_due;
        $status = 'unpaid';

        return [
            'tenant_id' => $tenant->id,
            'room_id' => $tenant->room_id,
            'amount_due' => $amount_due,
            'amount_paid' => $amount_paid,
            'remaining_amount' => $remaining_amount,
            'due_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'paid_date' => null,
            'status' => $status,
            'payment_method' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $attributes['amount_due'],
            'remaining_amount' => 0,
            'status' => 'paid',
            'paid_date' => $this->faker->dateTime(),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'e_wallet']),
        ]);
    }

    /**
     * Indicate that the payment is partial.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $amount_paid = $attributes['amount_due'] * 0.5;
            return [
                'amount_paid' => $amount_paid,
                'remaining_amount' => $attributes['amount_due'] - $amount_paid,
                'status' => 'partial',
                'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'e_wallet']),
            ];
        });
    }
}
