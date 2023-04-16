<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\ListUpcomingSpeakingClubs;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListUpcomingSpeakingClubsCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private Clock $clock,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(ListUpcomingSpeakingClubsCommand $command): void
    {
        $speakingClubs = $this->speakingClubRepository->findAllUpcoming($this->clock->now());

        $buttons = [];
        foreach ($speakingClubs as $speakingClub) {
            $buttons[] = [
                [
                    'text' => sprintf('%s - %s', $speakingClub->getName(), $speakingClub->getDate()->format('d.m.Y H:i')),
                    'callback_data' => 'identifier',
                ],
            ];
        }

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'Список ближайших клубов',
            replyMarkup: $buttons
        );
    }
}
