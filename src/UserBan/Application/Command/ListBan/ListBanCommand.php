<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\ListBan;

class ListBanCommand
{
    public const COMMAND_NAME = '/bans';

    public const COMMAND_DESCRIPTION = 'Список забаненных пользователей';

    public function __construct(
        public int $chatId,
        public ?int $messageId = null,
    ) {
    }
}
