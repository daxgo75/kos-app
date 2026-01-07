<?php

namespace App\Models;

use App\Traits\PaymentStatusTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, PaymentStatusTrait;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'amount_due',
        'amount_paid',
        'remaining_amount',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    /**
     * Set remaining amount automatically when amount_due or amount_paid changes
     */
    protected static function booted()
    {
        static::saving(function ($payment) {
            $payment->remaining_amount = max(0, $payment->amount_due - $payment->amount_paid);
        });
    }

    /**
     * Get the tenant associated with the payment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the room associated with the payment.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is partially paid.
     */
    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || (!$this->isPaid() && $this->due_date < now()->toDateString());
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(?string $paymentMethod = null): void
    {
        $this->update([
            'status' => 'paid',
            'amount_paid' => $this->amount_due,
            'remaining_amount' => 0,
            'paid_date' => now()->toDateString(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
        ]);

        // Dispatch event
        \App\Events\PaymentMarkedAsPaid::dispatch($this);
    }

    /**
     * Add payment (installment).
     */
    public function addPayment(float $paidAmount, ?string $paymentMethod = null): void
    {
        $newPaidAmount = $this->amount_paid + $paidAmount;
        $newRemainingAmount = $this->amount_due - $newPaidAmount;

        $status = match (true) {
            $newRemainingAmount <= 0 => 'paid',
            $newPaidAmount > 0 => 'partial',
            default => 'pending',
        };

        $this->update([
            'amount_paid' => $newPaidAmount,
            'remaining_amount' => max(0, $newRemainingAmount),
            'status' => $status,
            'paid_date' => $newRemainingAmount <= 0 ? now()->toDateString() : $this->paid_date,
            'payment_method' => $paymentMethod ?? $this->payment_method,
        ]);

        // Dispatch event
        \App\Events\PaymentReceived::dispatch($this, $paidAmount);
    }
}
