<?php

declare(strict_types=1);

namespace App\User\Application\Command\User;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\RatingRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserGenericTextCommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SpeakingClubRepository $speakingClubRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly ParticipationRepository $participationRepository,
        private readonly TelegramInterface $telegram,
        private readonly Clock $clock,
    ) {
    }

    public function __invoke(UserGenericTextCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Что-то пошло не так, попробуйте еще раз',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_SPEAKING_CLUB_FEEDBACK) {
            $speakingClubId = $user->getActualSpeakingClubData()['id'] ?? null;

            if ($speakingClubId === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Что-то пошло не так, попробуйте еще раз',
                    replyMarkup: [[
                        [
                            'text' => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]],
                );
                return;
            }

            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Клуб не найден',
                    replyMarkup: [[
                        [
                            'text' => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]]
                );
                return;
            }

            $rating = $this->ratingRepository->findBySpeakingClubIdAndUserId($speakingClubId, $user->getId());
            $rating->setComment($command->text);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Спасибо за отзыв! 😊',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_PLUS_ONE_NAME) {
            $speakingClubIdString = $user->getActualSpeakingClubData()['speakingClubId'] ?? null;

            if ($speakingClubIdString === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Что-то пошло не так, попробуйте еще раз',
                    replyMarkup: [[
                        [
                            'text' => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]],
                );
                return;
            }

            $speakingClubId = Uuid::fromString($speakingClubIdString);
            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Клуб не найден',
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
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

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

            $plusOneName = trim($command->text);
            if (empty($plusOneName)) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Пожалуйста, укажите имя второго участника (+1):',
                    replyMarkup: []
                );
                return;
            }

            $participation = $this->participationRepository->findByUserIdAndSpeakingClubId(
                $user->getId(),
                $speakingClubId
            );
            if ($participation === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Вы не записаны на этот клуб',
                    replyMarkup: [[
                        [
                            'text' => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]]
                );
                return;
            }


            $participation->setIsPlusOne(true);
            $participation->setPlusOneName($plusOneName);
            $this->participationRepository->save($participation);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: sprintf('👌 Участник добавлен: %s', $plusOneName),
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ваших клубов',
                        'callback_data' => 'back_to_my_list',
                    ],
                ]]
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);
        }
    }
}
