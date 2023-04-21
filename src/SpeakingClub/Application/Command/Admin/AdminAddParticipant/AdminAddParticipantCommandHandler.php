<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminAddParticipant;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminAddParticipantCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(AdminAddParticipantCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Вы не зарегистрированы в системе',
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_PARTICIPANT);
        $user->setActualSpeakingClubData([
            'participantSpeakingClubId' => $command->speakingClubId->toString(),
        ]);
        $this->userRepository->save($user);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Введите имя участника',
        );
    }
}
