<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminRemoveParticipant;

use Ramsey\Uuid\UuidInterface;

class AdminRemoveParticipantCommand
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public UuidInterface $participationId,
    ) {
    }
}
