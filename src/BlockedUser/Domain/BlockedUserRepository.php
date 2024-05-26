<?php

declare(strict_types=1);

namespace App\BlockedUser\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface BlockedUserRepository
{
    public function save(BlockedUser $blockedUser): void;

    public function remove(BlockedUser $blockedUser): void;

    public function findById(UuidInterface $id): ?BlockedUser;

    /**
     * @param UuidInterface $userId
     * @param DateTimeImmutable $now
     */
    public function findByUserId(UuidInterface $userId, DateTimeImmutable $now): ?BlockedUser;

    /**
     * @return array<BlockedUser>|null
     */
    public function findAll(): ?array;
}
