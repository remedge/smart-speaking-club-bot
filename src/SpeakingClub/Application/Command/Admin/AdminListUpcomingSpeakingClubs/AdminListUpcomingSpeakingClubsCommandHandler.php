<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminListUpcomingSpeakingClubsCommandHandler
{
    public function __invoke(AdminListUpcomingSpeakingClubsCommand $command): void
    {
    }
}
