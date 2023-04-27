<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface UserRolesProvider
{
    public function isUserAdmin(string $username): bool;

    /**
     * @return array<string>
     */
    public function getAdminUsernames(): array;
}
