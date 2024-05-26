<?php

declare(strict_types=1);

namespace App\BlockedUser\Application\Command\BlockUser;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BlockUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(BlockUserCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Такого пользователя не существует'
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_USERNAME_TO_BLOCK);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            $command->chatId,
            '📝 Введите username участника(без "@"), которого хотите заблокировать'
        );
    }
}
