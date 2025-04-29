<?php

declare(strict_types=1);

namespace App\SpeakingClub\Presentation\Cli;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
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
        private UserRepository $userRepository,
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
                $this->sendMessage((int)$participation['chatId'], $text, $speakingClub->getName(), $speakingClub->getLink());
            }

            if (!is_null($speakingClub->getTeacherUsername())) {
                $teacher = $this->userRepository->findByUsername($speakingClub->getTeacherUsername());
                if ($teacher) {
                    $this->sendMessage($teacher->getChatId(), $text, $speakingClub->getName(), $speakingClub->getLink());
                }
            }
        }
    }

    private function sendMessage(int $chatId, string $text, string $speakingClubName, string $speakingClubLink): void
    {
        $this->telegram->sendMessage($chatId, sprintf($text, $speakingClubName, $speakingClubLink));
    }
}
