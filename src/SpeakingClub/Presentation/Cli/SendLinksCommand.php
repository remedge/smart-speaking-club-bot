<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:send-links', description: 'Send links to clubs to users 15 min before is starts.')]
class SendLinksCommand extends Command
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
        $startDate = $this->clock->now()->modify('+15 minutes');
        $startDate = $startDate->setTime((int)$startDate->format('H'), (int)$startDate->format('i'));

        $endDate = $this->clock->now()->modify('+15 minutes');
        $endDate = $endDate->setTime((int)$endDate->format('H'), (int)$endDate->format('i'), 59);

        $speakingClubs = $this->speakingClubRepository->findBetweenDates($startDate, $endDate, true);

        $this->notify($speakingClubs);

        return Command::SUCCESS;
    }

    /**
     * @param SpeakingClub[] $speakingClubs
     * @return void
     */
    private function notify(array $speakingClubs): void
    {
        $text = 'Разговорный клуб "%s" начнется через 15 минут! Ждём вас по ссылке ниже. Приятного общения! 😊' . "\n%s";
        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            foreach ($participations as $participation) {
                $this->telegram->sendMessage(
                    (int)$participation['chatId'],
                    sprintf(
                        $text,
                        $speakingClub->getName(),
                        $speakingClub->getLink()
                    )
                );
            }
        }
    }
}
