<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminShowParticipants;

use Ramsey\Uuid\UuidInterface;

class AdminShowParticipantsCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
