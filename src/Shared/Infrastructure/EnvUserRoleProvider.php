<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\UserRolesProvider;

class EnvUserRoleProvider implements UserRolesProvider
{
    /**
     * @param array<string> $adminChatUsernames
     */
    public function __construct(
        private array $adminChatUsernames,
    ) {
    }

    public function isUserAdmin(string $username): bool
    {
        return in_array(strtolower($username), $this->adminChatUsernames, true);
    }

    public function getAdminUsernames(): array
    {
        return $this->adminChatUsernames;
    }
}
