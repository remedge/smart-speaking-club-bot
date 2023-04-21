<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminRemovePlusOneToParticipant;

use Ramsey\Uuid\UuidInterface;

class AdminRemovePlusOneToParticipantCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $participationId,
    ) {
    }
}
