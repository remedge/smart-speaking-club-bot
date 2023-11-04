<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\AddBan;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddBanCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AddBanCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Такого пользователя не существует'
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_ADD_BAN);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            $command->chatId,
            '📝 Введите никнейм участника для бана'
        );
    }
}
