<?php

declare(strict_types=1);

namespace App\BlockedUser\Application\Command\BlockedUsersList;

class BlockedUsersListCommand
{
    public const COMMAND_NAME = '/blocked_users';

    public const COMMAND_DESCRIPTION = 'Список заблокированных пользователей';

    public function __construct(
        public int $chatId,
        public ?int $messageId = null,
    ) {
    }
}
