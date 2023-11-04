<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\RemoveBan;

use App\Shared\Domain\TelegramInterface;
use App\UserBan\Domain\UserBanRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveBanCommandHandler
{
    public function __construct(
        private UserBanRepository $userBanRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(RemoveBanCommand $command): void
    {
        $userBan = $this->userBanRepository->findById($command->banId);

        if ($userBan === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Пользователь не забанен'
            );
            return;
        }

        $this->userBanRepository->remove($userBan);

        $this->telegram->sendMessage(
            $command->chatId,
            'Пользователь успешно разбанен'
        );
    }
}
