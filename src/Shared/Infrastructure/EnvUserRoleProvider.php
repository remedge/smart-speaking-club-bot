<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\UserRolesProvider;

class EnvUserRoleProvider implements UserRolesProvider
{
    /**
     * @param array<int> $adminChatIds
     */
    public function __construct(
        private array $adminChatIds,
    ) {
    }

    public function isUserAdmin(int $chatId): bool
    {
        return in_array($chatId, $this->adminChatIds, true);
    }

    public function getAdminChatIds(): array
    {
        return $this->adminChatIds;
    }
}
