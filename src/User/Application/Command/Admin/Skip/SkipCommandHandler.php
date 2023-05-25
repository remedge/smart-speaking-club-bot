<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\Skip;

use App\Shared\Application\Command\Start\StartCommand;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SkipCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SkipCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            return;
        }

        $user->setState(UserStateEnum::IDLE);
        $user->setActualSpeakingClubData([]);
        $this->userRepository->save($user);

        $this->commandBus->dispatch(new StartCommand($command->chatId, $command->isAdmin));
    }
}
