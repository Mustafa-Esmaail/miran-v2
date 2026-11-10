<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function countByRole(string $role): int
    {
        return User::query()->role($role, 'web')->count();
    }
}
