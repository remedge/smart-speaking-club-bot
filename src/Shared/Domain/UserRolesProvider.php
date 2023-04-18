<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface UserRolesProvider
{
    public function isUserAdmin(int $chatId): bool;

    /**
     * @return array<int>
     */
    public function getAdminChatIds(): array;
}
