<?php

declare(strict_types=1);

namespace App\User\Domain;

interface UserRepository
{
    public function save(User $user): void;

    public function findByChatId(int $chatId): ?User;

    public function getByChatId(int $chatId): User;
}
