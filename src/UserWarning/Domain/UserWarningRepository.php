<?php

declare(strict_types=1);

namespace App\UserWarning\Domain;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

interface UserWarningRepository
{
    public function save(UserWarning $userWarning): void;

    public function remove(UserWarning $userWarning): void;

    public function findById(UuidInterface $id): ?UserWarning;

    /**
     * @return array<UserWarning>|null
     */
    public function findAllWarning(): ?array;

    /**
     * @return array<UserWarning>
     */
    public function findUserUpcoming(UuidInterface $userId, DateTimeImmutable $now): array;
}
