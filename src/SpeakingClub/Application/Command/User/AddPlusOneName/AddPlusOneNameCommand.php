<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\AddPlusOneName;

use Ramsey\Uuid\UuidInterface;

class AddPlusOneNameCommand
{
    public const CALLBACK_NAME = 'add_plus_one_name';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
