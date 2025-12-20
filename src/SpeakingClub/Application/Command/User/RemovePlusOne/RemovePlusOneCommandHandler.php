<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\RemovePlusOne;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Event\SpeakingClubFreeSpaceAvailableEvent;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemovePlusOneCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private EventDispatcherInterface $eventDispatcher,
        private Clock $clock,
    ) {
    }

    public function __invoke(RemovePlusOneCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Разговорный клуб не найден',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        if ($this->clock->now() > $speakingClub->getDate()) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '🤔 К сожалению, этот разговорный клуб уже прошел',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId($user->id, $command->speakingClubId);
        if ($participation === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не записаны на этот клуб',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        if ($participation->isPlusOne() === false) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не добавляли +1 с собой на этот клуб',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participation->setIsPlusOne(false);
        $participation->setPlusOneName(null);
        $this->participationRepository->save($participation);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: '👌Вы успешно убрали +1 человека с собой',
            replyMarkup: [[
                [
                    'text' => 'Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ],
            ]]
        );

        $this->eventDispatcher->dispatch(new SpeakingClubFreeSpaceAvailableEvent($speakingClub->getId()));
    }
}
