<?php

declare(strict_types=1);

namespace App\WaitList\Application\Command\JoinWaitingList;

use Ramsey\Uuid\UuidInterface;

class JoinWaitingListCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
