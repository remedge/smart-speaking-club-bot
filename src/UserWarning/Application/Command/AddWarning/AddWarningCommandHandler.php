<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\AddWarning;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddWarningCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AddWarningCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Такого пользователя не существует'
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_ADD_WARNING);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            $command->chatId,
            '📝 Введите никнейм участника для предупреждения'
        );
    }
}
