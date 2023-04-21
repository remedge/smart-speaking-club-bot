<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminAddPlusOneToParticipant;

use Ramsey\Uuid\UuidInterface;

class AdminAddPlusOneToParticipantCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $participationId,
    ) {
    }
}
