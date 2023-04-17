<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs;

class ListUserUpcomingSpeakingClubsCommand
{
    public function __construct(
        public int $chatId,
    ) {
    }
}
