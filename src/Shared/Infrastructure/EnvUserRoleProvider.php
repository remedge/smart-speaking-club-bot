<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\UserRolesProvider;

class EnvUserRoleProvider implements UserRolesProvider
{
    /**
     * @param array<string> $adminUsernames
     */
    public function __construct(
        private array $adminUsernames,
    ) {
    }

    public function isUserAdmin(string $username): bool
    {
        return in_array($username, $this->adminUsernames, true);
    }
}
