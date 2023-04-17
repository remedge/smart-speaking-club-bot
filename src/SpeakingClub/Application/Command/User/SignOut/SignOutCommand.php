<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignOut;

use Ramsey\Uuid\UuidInterface;

class SignOutCommand
{
    public const CALLBACK_NAME = 'sign_out';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
    ) {
    }
}
