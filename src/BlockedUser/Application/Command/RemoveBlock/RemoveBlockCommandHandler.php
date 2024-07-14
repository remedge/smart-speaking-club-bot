<?php

declare(strict_types=1);

namespace App\BlockedUser\Application\Command\RemoveBlock;

use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Domain\TelegramInterface;
use App\UserBan\Application\Command\RemoveBan\RemoveBanCommand;
use App\UserBan\Domain\UserBanRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveBlockCommandHandler
{
    public function __construct(
        private BlockedUserRepository $blockedUserRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(RemoveBlockCommand $command): void
    {
        $blockedUser = $this->blockedUserRepository->findById($command->blockId);

        if ($blockedUser === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Пользователь не заблокирован'
            );
            return;
        }

        $this->blockedUserRepository->remove($blockedUser);

        $this->telegram->sendMessage(
            $command->chatId,
            'Пользователь успешно разблокирован'
        );
    }
}
