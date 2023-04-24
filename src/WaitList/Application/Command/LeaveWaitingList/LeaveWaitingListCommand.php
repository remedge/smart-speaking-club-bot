<?php

declare(strict_types=1);

namespace App\WaitList\Application\Command\LeaveWaitingList;

use Ramsey\Uuid\UuidInterface;

class LeaveWaitingListCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
