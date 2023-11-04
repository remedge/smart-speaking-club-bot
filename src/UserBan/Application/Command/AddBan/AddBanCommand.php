<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\AddBan;

class AddBanCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
    ) {
    }
}
