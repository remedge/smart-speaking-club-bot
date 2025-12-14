<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\AddPlusOneName;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddPlusOneNameCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private UserRepository $userRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private TelegramInterface $telegram,
        private Clock $clock,
    ) {
    }

    public function __invoke(AddPlusOneNameCommand $command): void
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
                        'text' => '<< Перейти к списку ближайших клубов',
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
        if ($participation === null || $participation->isPlusOne() === false) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не записаны с +1 на этот клуб',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $userEntity = $this->userRepository->findByChatId($command->chatId);
        if ($userEntity === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Что-то пошло не так, попробуйте еще раз',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $userEntity->setState(UserStateEnum::RECEIVING_PLUS_ONE_NAME);
        $userEntity->setActualSpeakingClubData([
            'speakingClubId' => $command->speakingClubId->toString(),
        ]);
        $this->userRepository->save($userEntity);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Пожалуйста, укажите имя второго участника (+1):',
            replyMarkup: []
        );
    }
}
