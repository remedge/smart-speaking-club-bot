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
        $this->notify(27, '27 часов');
        $this->notify(2, '2 часа');

        return Command::SUCCESS;
    }

    private function notify(int $addHours, string $timeMessage): void
    {
        $startDate = $this->clock->now()->modify('+' . $addHours . ' hours');
        $startDate = $startDate->setTime((int)$startDate->format('H'), 0, 0);

        $endDate = $this->clock->now()->modify('+' . $addHours . ' hours');
        $endDate = $endDate->setTime((int)$endDate->format('H'), 59, 0);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate);

        $text = 'Разговорный клуб "%s" начнется через %s. Если у вас не получается прийти, пожалуйста, ' .
            'отмените вашу запись, чтобы мы предложили ваше место другим.';
        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            foreach ($participations as $participation) {
                $this->telegram->sendMessage(
                    (int)$participation['chatId'],
                    sprintf(
                        $text,
                        $speakingClub->getName(),
                        $timeMessage
                    )
                );
            }
        }
    }
}
