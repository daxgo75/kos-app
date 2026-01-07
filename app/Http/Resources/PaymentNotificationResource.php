<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount_due' => $this->amount_due,
            'amount_paid' => $this->amount_paid,
            'remaining_amount' => $this->remaining_amount,
            'due_date' => $this->due_date->toDateString(),
            'paid_date' => $this->paid_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'tenant' => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ],
            'room' => [
                'id' => $this->room->id,
                'room_number' => $this->room->room_number,
            ],
        ];
    }
}
