<?php

declare(strict_types=1);

namespace App\User\Application\Command\User;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\RatingRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserGenericTextCommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SpeakingClubRepository $speakingClubRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly TelegramInterface $telegram,
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
    }
}
