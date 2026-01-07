<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'email',
        'phone',
        'check_in_date',
        'check_out_date',
        'status',
        'address',
        'notes',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    /**
     * Get the room associated with the tenant.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the payments for the tenant.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get unpaid payments.
     */
    public function unpaidPayments()
    {
        return $this->payments()
            ->where('status', '!=', 'paid')
            ->orderBy('due_date');
    }

    /**
     * Get total amount due.
     */
    public function getTotalAmountDue(): float
    {
        return $this->payments()
            ->where('status', '!=', 'paid')
            ->sum('amount_due');
    }

    /**
     * Get total remaining amount.
     */
    public function getTotalRemainingAmount(): float
    {
        return $this->payments()
            ->where('status', '!=', 'paid')
            ->sum('remaining_amount');
    }

    /**
     * Get total paid amount.
     */
    public function getTotalPaidAmount(): float
    {
        return $this->payments()
            ->sum('amount_paid');
    }

    /**
     * Check if tenant has any unpaid payments.
     */
    public function hasUnpaidPayments(): bool
    {
        return $this->unpaidPayments()->exists();
    }
}
