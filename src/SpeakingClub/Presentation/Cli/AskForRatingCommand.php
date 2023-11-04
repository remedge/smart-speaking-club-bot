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

#[AsCommand(name: 'app:ask-for-rating', description: 'Ask for rating for past speaking clubs')]
class AskForRatingCommand extends Command
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private TelegramInterface $telegram,
        private Clock $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentTime = $this->clock->now()->modify('-2 hours');

        $speakingClubs = $this->speakingClubRepository->findAllPastNotAskedForRating($currentTime);

        foreach ($speakingClubs as $speakingClub) {
            $participations = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());

            foreach ($participations as $participation) {
                $this->telegram->sendMessage(
                    chatId: (int) $participation['chatId'],
                    text: sprintf('Cпасибо за то, что посетили разговорный клуб "%s"! Поделитесь пожалуйста своими впечатлениями: ', $speakingClub->getName()),
                    replyMarkup: [[
                        [
                            'text' => '🥱',
                            'callback_data' => sprintf('rate:%s:1', $speakingClub->getId()),
                        ],
                        [
                            'text' => '😐',
                            'callback_data' => sprintf('rate:%s:2', $speakingClub->getId()),
                        ],
                        [
                            'text' => '🙂',
                            'callback_data' => sprintf('rate:%s:3', $speakingClub->getId()),
                        ],
                        [
                            'text' => '🤩',
                            'callback_data' => sprintf('rate:%s:4', $speakingClub->getId()),
                        ],
                    ]]
                );
            }

            $this->speakingClubRepository->markRatingAsked($speakingClub->getId());
        }

        return Command::SUCCESS;
    }
}
