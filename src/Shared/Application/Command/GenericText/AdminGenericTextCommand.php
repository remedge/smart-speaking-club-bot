<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

class AdminGenericTextCommand
{
    public function __construct(
        public int $chatId,
        public string $text,
    ) {
    }
}
