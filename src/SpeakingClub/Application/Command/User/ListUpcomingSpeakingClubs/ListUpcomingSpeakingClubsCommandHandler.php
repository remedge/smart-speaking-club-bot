<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ListUpcomingSpeakingClubs;

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
                    'text' => sprintf('%s - %s', $speakingClub->getDate()->format('d.m.Y H:i'), $speakingClub->getName()),
                    'callback_data' => sprintf('show_speaking_club:%s', $speakingClub->getId()->toString()),
                ],
            ];
        }

        if (count($speakingClubs) === 0) {
            $text = 'Пока мы не запланировали ни одного клуба. Попробуйте позже.';
        } else {
            $text = 'Список ближайших разговорных клубов и культурных мероприятий. Нажмите на один из них, чтобы увидеть подробную информацию';
        }

        if ($command->messageId !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: $text,
                replyMarkup: $buttons
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: $text,
                replyMarkup: $buttons
            );
        }
    }
}
