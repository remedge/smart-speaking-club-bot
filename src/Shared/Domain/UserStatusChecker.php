<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface UserStatusChecker
{
    public function checkIsBlocked(string $username): bool;
}
