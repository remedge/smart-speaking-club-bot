<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitSendMessageToParticipants;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitSendMessageToParticipantsCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(InitSendMessageToParticipantsCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Такого пользователя не существует'
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_MESSAGE_FOR_PARTICIPANTS);
        $user->setActualSpeakingClubData([
            'id' => $command->speakingClubId->toString(),
        ]);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            $command->chatId,
            '📝 Введите сообщение, которое хотите отправить участникам клуба'
        );
    }
}
