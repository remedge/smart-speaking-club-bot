<?php

declare(strict_types=1);

namespace App\UserBan\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface UserBanRepository
{
    public function save(UserBan $userBan): void;

    public function remove(UserBan $userBan): void;

    public function findById(UuidInterface $id): ?UserBan;

    /**
     * @param UuidInterface $userId
     * @param DateTimeImmutable $now
     */
    public function findByUserId(UuidInterface $userId, DateTimeImmutable $now): ?UserBan;

    /**
     * @return array<UserBan>|null
     */
    public function findAllBan(): ?array;

    /**
     * @return array<UserBan>|null
     */
    public function findAllBanNow(DateTimeImmutable $now): ?array;
}
