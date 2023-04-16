<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface UserRolesProvider
{
    public function isUserAdmin(string $username): bool;
}
