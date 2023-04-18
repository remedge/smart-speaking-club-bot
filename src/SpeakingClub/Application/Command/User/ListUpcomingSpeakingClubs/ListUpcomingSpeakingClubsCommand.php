<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs;

class ListUpcomingSpeakingClubsCommand
{
    public const COMMAND_NAME = '/upcoming_clubs';

    public const COMMAND_DESCRIPTION = 'Список клубов, которые будут в ближайшее время';

    public function __construct(
        public int $chatId,
        public ?int $messageId = null,
    ) {
    }
}
