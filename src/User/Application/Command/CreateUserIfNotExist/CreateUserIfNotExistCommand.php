<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUserIfNotExist;

class CreateUserIfNotExistCommand
{
    public function __construct(
        public int $chatId,
        public string $firstName,
        public string $lastName,
        public string $userName,
    ) {
    }
}
