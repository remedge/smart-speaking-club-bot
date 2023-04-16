<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Start;

class StartCommand
{
    public function __construct(
        public int $chatId,
    ) {
    }
}
