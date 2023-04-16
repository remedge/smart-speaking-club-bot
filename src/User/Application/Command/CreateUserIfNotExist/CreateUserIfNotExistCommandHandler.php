<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUserIfNotExist;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateUserIfNotExistCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UuidProvider $uuidProvider,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateUserIfNotExistCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);

        if ($user === null) {
            $userId = $this->uuidProvider->provide();
            $this->userRepository->save(new User(
                id: $userId,
                chatId: $command->chatId,
                firstName: $command->firstName,
                lastName: $command->lastName,
                username: $command->userName,
                createdAt: $this->clock->now(),
            ));
        }
    }
}
