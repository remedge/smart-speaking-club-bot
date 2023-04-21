<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminAddParticipant;

use Ramsey\Uuid\UuidInterface;

class AdminAddParticipantCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $speakingClubId,
    ) {
    }
}
