<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitSendMessageEveryone;

class InitSendMessageEveryoneCommand
{
    public function __construct(
        public int $chatId,
    ) {
    }
}
