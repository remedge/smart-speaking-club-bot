<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:notify-users', description: 'Every hour speaking club check')]
class NotifyUsersAboutCloseClubsCommand extends Command
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private Clock $clock,
        private TelegramInterface $telegram,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startDate = $this->clock->now()->modify('+24 hours');
        $startDate = $startDate->setTime((int) $startDate->format('H'), 0, 0);

        $endDate = $this->clock->now()->modify('+24 hours');
        $endDate = $endDate->setTime((int) $endDate->format('H'), 59, 0);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate);

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            foreach ($participations as $participation) {
                $this->telegram->sendMessage($participation['chatId'], sprintf('Разговорный клуб "%s" начнется через 24 часа', $speakingClub->getName()));
            }
        }

        $startDate = $this->clock->now()->modify('+2 hours');
        $startDate = $startDate->setTime((int) $startDate->format('H'), 0, 0);

        $endDate = $this->clock->now()->modify('+2 hours');
        $endDate = $endDate->setTime((int) $endDate->format('H'), 59, 0);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate);

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            foreach ($participations as $participation) {
                $this->telegram->sendMessage($participation['chatId'], sprintf('Разговорный клуб "%s" начнется через 2 часа', $speakingClub->getName()));
            }
        }

        return Command::SUCCESS;
    }
}
