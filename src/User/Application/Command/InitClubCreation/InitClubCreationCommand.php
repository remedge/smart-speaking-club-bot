<?php

declare(strict_types=1);

namespace App\User\Application\Command\InitClubCreation;

class InitClubCreationCommand
{
    public function __construct(
        public int $chatId,
    ) {
    }
}
