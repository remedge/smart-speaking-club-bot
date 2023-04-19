<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubEdition;

use Ramsey\Uuid\UuidInterface;

class InitClubEditionCommand
{
    public function __construct(
        public int $chatId,
        public UuidInterface $speakingClubId,
        public int $messageId,
    ) {
    }
}
