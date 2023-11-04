<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\ListWarning;

class ListWarningCommand
{
    public const COMMAND_NAME = '/warnings';

    public const COMMAND_DESCRIPTION = 'Список пользователей с предупреждением';

    public function __construct(
        public int $chatId,
        public ?int $messageId = null,
    ) {
    }
}
