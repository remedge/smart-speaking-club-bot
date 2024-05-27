<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Domain\UserStatusChecker;

class DbUserStatusChecker implements UserStatusChecker
{
    public function __construct(private BlockedUserRepository $blockedUserRepository)
    {
    }

    public function checkIsBlocked(string $username): bool
    {
        return (bool) $this->blockedUserRepository->findByUsername($username);
    }
}
