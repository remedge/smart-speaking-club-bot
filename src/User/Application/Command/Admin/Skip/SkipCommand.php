<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\Skip;

class SkipCommand
{
    public function __construct(
        public int $chatId,
        public bool $isAdmin,
    ) {
    }
}
