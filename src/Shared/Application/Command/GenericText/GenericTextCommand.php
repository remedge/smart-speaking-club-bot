<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

class GenericTextCommand
{
    public function __construct(
        public int $chatId,
        public string $text,
    ) {
    }
}
