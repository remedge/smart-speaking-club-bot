<?php

declare(strict_types=1);

namespace App\WaitList\Domain;

use Ramsey\Uuid\UuidInterface;

interface WaitingUserRepository
{
    public function save(WaitingUser $waitingUser): void;

    public function remove(WaitingUser $waitingUser): void;

    public function findByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?WaitingUser;
}
