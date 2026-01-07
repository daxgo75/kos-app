<?php

namespace App\Traits;

use App\Models\AdminNotification;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasAdminNotifications
{
    public function adminNotifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class, 'user_id');
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->adminNotifications()
            ->where('is_read', false)
            ->count();
    }

    public function getUnreadNotifications()
    {
        return $this->adminNotifications()
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->get();
    }
}
