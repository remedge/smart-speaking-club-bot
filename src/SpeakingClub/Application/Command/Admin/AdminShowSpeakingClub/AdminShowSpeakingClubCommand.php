<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminShowSpeakingClub;

use Ramsey\Uuid\UuidInterface;

class AdminShowSpeakingClubCommand
{
    public const CALLBACK_NAME = 'admin_show_speaking_club';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
