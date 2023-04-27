<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitSendMessageEveryone;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitSendMessageEveryoneCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(InitSendMessageEveryoneCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Такого пользователя не существует'
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_MESSAGE_FOR_EVERYONE);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            $command->chatId,
            '📝 Введите сообщение, которое хотите отправить всем пользователям'
        );
    }
}
