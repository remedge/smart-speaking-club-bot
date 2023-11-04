<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\RemoveWarning;

use Ramsey\Uuid\UuidInterface;

class RemoveWarningCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $warningId,
    ) {
    }
}
