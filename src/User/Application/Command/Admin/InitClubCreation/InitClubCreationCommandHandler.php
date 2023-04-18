<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubCreation;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitClubCreationCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(InitClubCreationCommand $command): void
    {
        $user = $this->userRepository->getByChatId($command->chatId);

        $user->setState(UserStateEnum::RECEIVING_NAME_FOR_CREATING);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'Введите название клуба',
        );
    }
}
