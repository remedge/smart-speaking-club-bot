<?php

declare(strict_types=1);

namespace App\User\Application\Command\User;

class UserGenericTextCommand
{
    public function __construct(
        public int $chatId,
        public string $text,
    ) {
    }
}
