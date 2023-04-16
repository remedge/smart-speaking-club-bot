<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\ShowSpeakingClub;

use Ramsey\Uuid\UuidInterface;

class ShowSpeakingClubCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId
    ) {
    }
}
