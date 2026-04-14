<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Widget;

class WidgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Widget $widget): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Widget $widget): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Widget $widget): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Widget $widget): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Widget $widget): bool
    {
        return $user->hasRole('admin');
    }
}
