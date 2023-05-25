<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubCancellation;

use Ramsey\Uuid\UuidInterface;

class InitClubCancellationCommand
{
    public function __construct(
        public UuidInterface $speakingClubId,
        public int $chatId,
        public int $messageId,
    ) {
    }
}
