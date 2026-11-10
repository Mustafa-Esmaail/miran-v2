<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function countByRole(string $role): int;
}
