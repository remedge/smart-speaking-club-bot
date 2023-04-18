<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Help;

class HelpCommand
{
    public const COMMAND_NAME = '/help';

    public const COMMAND_DESCRIPTION = 'Показать список команд';

    public function __construct(
        public int $chatId,
        public bool $isAdmin,
    ) {
    }
}
