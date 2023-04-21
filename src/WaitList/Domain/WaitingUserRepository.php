<?php

declare(strict_types=1);

namespace App\WaitList\Domain;

use Ramsey\Uuid\UuidInterface;

interface WaitingUserRepository
{
    public function save(WaitingUser $waitingUser): void;

    public function remove(WaitingUser $waitingUser): void;

    public function findById(UuidInterface $id): ?WaitingUser;

    /**
     * @return array{id: UuidInterface, userId: UuidInterface, speakingClubId: UuidInterface, chatId: int}|null
     */
    public function findOneByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?array;

    /**
     * @return array<array{id: UuidInterface, userId: UuidInterface, speakingClubId: UuidInterface, chatId: int}>
     */
    public function findBySpeakingClubId(UuidInterface $speakingClubId): array;
}
