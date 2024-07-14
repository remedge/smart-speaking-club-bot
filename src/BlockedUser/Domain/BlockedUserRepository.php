<?php

declare(strict_types=1);

namespace App\BlockedUser\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface BlockedUserRepository
{
    public function save(BlockedUser $blockedUser): void;

    public function remove(BlockedUser $blockedUser): void;

    /**
     * @param UuidInterface $id
     * @return BlockedUser|null
     */
    public function findById(UuidInterface $id): ?BlockedUser;
    public function findByUserName(string $username): ?BlockedUser;

    /**
     * @param UuidInterface $userId
     * @return BlockedUser|null
     */
    public function findByUserId(UuidInterface $userId): ?BlockedUser;

    public function findAll(): ?array;
}
