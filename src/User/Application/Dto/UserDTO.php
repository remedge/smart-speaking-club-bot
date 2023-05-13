<?php

declare(strict_types=1);

namespace App\User\Application\Dto;

use Ramsey\Uuid\UuidInterface;

class UserDTO
{
    public function __construct(
        public UuidInterface $id,
        public int $chatId,
        public ?string $firstName,
        public ?string $lastName,
        public string $username,
    ) {
    }
}
