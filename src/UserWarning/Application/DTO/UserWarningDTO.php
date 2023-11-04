<?php

declare(strict_types=1);

namespace App\UserWarning\Application\DTO;

use Ramsey\Uuid\UuidInterface;

class UserWarningDTO
{
    public function __construct(
        public UuidInterface $id,
        public string $username,
        public ?string $firstName,
        public ?string $lastName,
        public int $chatId,
    ) {
    }
}
