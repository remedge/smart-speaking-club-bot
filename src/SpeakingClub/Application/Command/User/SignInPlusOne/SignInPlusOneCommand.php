<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignInPlusOne;

use Ramsey\Uuid\UuidInterface;

class SignInPlusOneCommand
{
    public const CALLBACK_NAME = 'sign_in_plus_one';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
