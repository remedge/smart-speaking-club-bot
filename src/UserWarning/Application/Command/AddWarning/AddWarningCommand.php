<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\AddWarning;

class AddWarningCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
    ) {
    }
}
