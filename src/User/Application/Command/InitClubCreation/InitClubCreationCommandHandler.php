<?php

declare(strict_types=1);

namespace App\User\Application\Command\InitClubCreation;

use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Longman\TelegramBot\Request;

#[AsMessageHandler]
class InitClubCreationCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    )
    {
    }

    public function __invoke(InitClubCreationCommand $command): void
    {
        $user = $this->userRepository->getByChatId($command->chatId);

        $user->setState(UserStateEnum::RECEIVING_NAME_FOR_CREATING);
        $this->userRepository->save($user);

        Request::sendMessage([
            'chat_id' => $command->chatId,
            'text' => 'Введите название клуба',
        ]);
    }
}