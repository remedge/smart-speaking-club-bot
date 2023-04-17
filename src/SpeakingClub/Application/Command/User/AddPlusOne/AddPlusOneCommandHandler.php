<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\AddPlusOne;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddPlusOneCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AddPlusOneCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб не найден',
            );
            return;
        }

        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId($user->id, $command->speakingClubId);
        if ($participation === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Вы не записаны на клуб',
            );
            return;
        }

        if ($participation->isPlusOne() === true) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Вы уже добавили +1',
            );
            return;
        }

        $participation->setIsPlusOne(true);
        $this->participationRepository->save($participation);

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'Вы успешно добавили +1 человека с собой',
        );
    }
}
