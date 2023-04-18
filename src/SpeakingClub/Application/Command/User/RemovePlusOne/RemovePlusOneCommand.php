<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\RemovePlusOne;

use Ramsey\Uuid\UuidInterface;

class RemovePlusOneCommand
{
    public const CALLBACK_NAME = 'remove_plus_one';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
