<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitSendMessageToParticipants;

use Ramsey\Uuid\UuidInterface;

class InitSendMessageToParticipantsCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
    ) {
    }
}
