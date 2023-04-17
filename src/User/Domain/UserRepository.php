<?php

declare(strict_types=1);

namespace App\User\Domain;

use Ramsey\Uuid\UuidInterface;

interface UserRepository
{
    public function save(User $user): void;

    public function findByChatId(int $chatId): ?User;

    public function findById(UuidInterface $id): ?User;

    public function getByChatId(int $chatId): User;
}
