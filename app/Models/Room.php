<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'room_type',
        'monthly_rate',
        'description',
        'status',
        'capacity',
        'occupied_at',
        'available_at',
    ];

    protected $casts = [
        'monthly_rate' => 'decimal:2',
        'occupied_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    /**
     * Get the tenants for the room.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get current active tenant.
     */
    public function activeTenant()
    {
        return $this->tenants()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get the payments for the room.
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
     * Get total outstanding balance.
     */
    public function getTotalOutstandingBalance(): float
    {
        return $this->unpaidPayments()
            ->sum('remaining_amount');
    }

    /**
     * Update room status based on active tenants.
     */
    public function updateStatus(): void
    {
        $hasActiveTenant = $this->tenants()->where('status', 'active')->exists();
        $previousStatus = $this->status;

        if ($hasActiveTenant && $previousStatus !== 'occupied') {
            $this->status = 'occupied';
            $this->occupied_at = now();
            $this->available_at = null;
        } elseif (!$hasActiveTenant && $previousStatus !== 'available') {
            $this->status = 'available';
            $this->available_at = now();
        }

        $this->save();
    }
}
