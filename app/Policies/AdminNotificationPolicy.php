<?php

namespace App\Policies;

use App\Models\AdminNotification;
use App\Models\User;

class AdminNotificationPolicy
{
    public function view(User $user, AdminNotification $adminNotification): bool
    {
        return $user->id === $adminNotification->user_id;
    }

    public function update(User $user, AdminNotification $adminNotification): bool
    {
        return $user->id === $adminNotification->user_id;
    }

    public function delete(User $user, AdminNotification $adminNotification): bool
    {
        return $user->id === $adminNotification->user_id;
    }
}
