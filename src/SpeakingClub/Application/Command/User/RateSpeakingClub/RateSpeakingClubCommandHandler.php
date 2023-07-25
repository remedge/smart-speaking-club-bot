<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\RateSpeakingClub;

use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\Rating;
use App\SpeakingClub\Domain\RatingRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RateSpeakingClubCommandHandler
{
    public function __construct(
        private readonly RatingRepository $ratingRepository,
        private readonly SpeakingClubRepository $speakingClubRepository,
        private readonly UserRepository $userRepository,
        private readonly UuidProvider $uuidProvider,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(RateSpeakingClubCommand $command): void
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

        // Rating should be between 1 and 4
        if ($command->rating < 1 || $command->rating > 4) {
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

        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
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

        $rating = $this->ratingRepository->findBySpeakingClubIdAndUserId($speakingClub->getId(), $user->getId());
        if ($rating !== null) {
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

        $this->ratingRepository->save(
            new Rating(
                id: $this->uuidProvider->provide(),
                userId: $user->getId(),
                speakingClubId: $command->speakingClubId,
                rating: $command->rating,
            )
        );

        $this->telegram->editMessageText(
            chatId: $user->getChatId(),
            messageId: $command->messageId,
            text: 'Спасибо за вашу оценку! Будем благодарны, если вы напишите пару слов о том, как прошел ваш разговорный клуб 😊Что вам понравилось и что можно сделать лучше в следующий раз? Если не хотите оставлять отзыв, просто нажмите /skip',
        );

        $user->setState(UserStateEnum::RECEIVING_SPEAKING_CLUB_FEEDBACK);
        $user->setActualSpeakingClubData([
            'id' => $speakingClub->getId(),
        ]);
        $this->userRepository->save($user);
    }
}
