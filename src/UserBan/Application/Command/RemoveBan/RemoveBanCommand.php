<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\RemoveBan;

use Ramsey\Uuid\UuidInterface;

class RemoveBanCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $banId,
    ) {
    }
}
