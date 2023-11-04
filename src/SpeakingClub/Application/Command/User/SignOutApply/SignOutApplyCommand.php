<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignOutApply;

use Ramsey\Uuid\UuidInterface;

class SignOutApplyCommand
{
    public const CALLBACK_NAME = 'sign_out_apply';

    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
