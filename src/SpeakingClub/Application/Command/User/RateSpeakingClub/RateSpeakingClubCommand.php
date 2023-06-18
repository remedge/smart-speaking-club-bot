<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\RateSpeakingClub;

use Ramsey\Uuid\UuidInterface;

class RateSpeakingClubCommand
{
    public function __construct(
        public UuidInterface $speakingClubId,
        public int $chatId,
        public int $messageId,
        public int $rating,
    ) {
    }
}
