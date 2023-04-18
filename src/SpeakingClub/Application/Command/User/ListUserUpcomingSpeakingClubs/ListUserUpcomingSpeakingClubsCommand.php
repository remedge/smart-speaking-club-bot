<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs;

class ListUserUpcomingSpeakingClubsCommand
{
    public const COMMAND_NAME = '/my_upcoming_clubs';

    public const COMMAND_DESCRIPTION = 'Список клубов, в которых вы будете участвовать';

    public function __construct(
        public int $chatId,
    ) {
    }
}
