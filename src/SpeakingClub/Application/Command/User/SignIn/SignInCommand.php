<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignIn;

use Ramsey\Uuid\UuidInterface;

class SignInCommand
{
    public const CALLBACK_NAME = 'sign_in';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
