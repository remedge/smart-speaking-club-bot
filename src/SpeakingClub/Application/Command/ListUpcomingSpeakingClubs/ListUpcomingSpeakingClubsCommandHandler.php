<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\ListUpcomingSpeakingClubs;

use App\Shared\Application\Clock;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Longman\TelegramBot\Request;

#[AsMessageHandler]
class ListUpcomingSpeakingClubsCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private Clock $clock,
    )
    {
    }

    public function __invoke(ListUpcomingSpeakingClubsCommand $command): void
    {
        $speakingClubs = $this->speakingClubRepository->findAllUpcoming($this->clock->now());

        $buttons = [];
        foreach ($speakingClubs as $speakingClub) {
            $buttons[] = [
                [
                    'text' => sprintf('%s - %s', $speakingClub->getName(), $speakingClub->getDate()->format('d.m.Y H:i')),
                    'callback_data' => 'identifier'
                ]
            ];
        }

        $inline_keyboard = new InlineKeyboard(...$buttons);

        Request::sendMessage([
            'chat_id' => $command->chatId,
            'text' => 'Список ближайших клубов',
            'reply_markup' => $inline_keyboard,
        ]);
    }
}