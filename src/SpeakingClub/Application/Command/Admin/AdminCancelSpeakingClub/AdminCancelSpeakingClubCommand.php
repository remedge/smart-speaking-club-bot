<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminCancelSpeakingClub;

use Ramsey\Uuid\UuidInterface;

class AdminCancelSpeakingClubCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
