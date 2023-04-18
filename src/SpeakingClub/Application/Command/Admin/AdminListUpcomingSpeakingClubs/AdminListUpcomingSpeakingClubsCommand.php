<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs;

class AdminListUpcomingSpeakingClubsCommand
{
    public const COMMAND_NAME = '/admin_upcoming_clubs';

    public const COMMAND_DESCRIPTION = 'Показать список ближайших разговорных клубов';

    public function __construct(
        public int $chatId,
    ) {
    }
}
