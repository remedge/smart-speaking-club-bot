<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\ListUpcomingSpeakingClubs;

class ListUpcomingSpeakingClubsCommand
{
    public function __construct(
        public int $chatId,
    )
    {
    }
}