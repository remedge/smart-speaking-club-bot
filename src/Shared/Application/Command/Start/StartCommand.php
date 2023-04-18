<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Start;

class StartCommand
{
    public const COMMAND_NAME = '/start';

    public const COMMAND_DESCRIPTION = 'Начать работу с ботом';

    public function __construct(
        public int $chatId,
        public bool $isAdmin,
    ) {
    }
}
