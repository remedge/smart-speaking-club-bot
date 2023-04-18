<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\AddPlusOne;

use Ramsey\Uuid\UuidInterface;

class AddPlusOneCommand
{
    public const CALLBACK_NAME = 'add_plus_one';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
