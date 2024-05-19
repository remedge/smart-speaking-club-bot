<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\BlockUser;

class BlockUserCommand
{
    public const COMMAND_NAME = '/block_user';

    public const COMMAND_DESCRIPTION = 'Заблокировать пользователя';

    public function __construct(
        public int $chatId,
        public ?int $messageId = null,
    ) {
    }
}
