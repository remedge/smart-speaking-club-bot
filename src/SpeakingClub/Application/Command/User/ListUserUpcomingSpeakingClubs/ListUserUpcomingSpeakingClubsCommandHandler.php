<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ListUserUpcomingSpeakingClubs;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListUserUpcomingSpeakingClubsCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private UserQuery $userQuery,
        private Clock $clock,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(ListUserUpcomingSpeakingClubsCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClubs = $this->speakingClubRepository->findUserUpcoming($user->id, $this->clock->now());

        $buttons = [];
        foreach ($speakingClubs as $speakingClub) {
            $buttons[] = [
                [
                    'text' => sprintf('%s - %s', $speakingClub->getDate()->format('d.m H:i'), $speakingClub->getName()),
                    'callback_data' => sprintf('show_my_speaking_club:%s', $speakingClub->getId()->toString()),
                ],
            ];
        }

        if (count($speakingClubs) === 0) {
            $text = 'Вы не записаны ни на один клуб. Выберите клуб из списка, чтобы записаться.';
            $buttons = [[
                [
                    'text' => 'Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ],
            ]];
        } else {
            $text = 'Список ближайших клубов, куда вы записаны:';
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
