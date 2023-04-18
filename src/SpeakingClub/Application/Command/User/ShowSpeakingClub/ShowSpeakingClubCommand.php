<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ShowSpeakingClub;

use Ramsey\Uuid\UuidInterface;

class ShowSpeakingClubCommand
{
    public const CALLBACK_NAME = 'show_speaking_club';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
