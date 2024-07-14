<?php

declare(strict_types=1);

namespace App\BlockedUser\Application\Command\RemoveBlock;

use Ramsey\Uuid\UuidInterface;

class RemoveBlockCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $blockId,
    ) {
    }
}
