<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\RemoveWarning;

use App\Shared\Domain\TelegramInterface;
use App\UserWarning\Domain\UserWarningRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveWarningCommandHandler
{
    public function __construct(
        private UserWarningRepository $userWarningRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(RemoveWarningCommand $command): void
    {
        $userWarning = $this->userWarningRepository->findById($command->warningId);

        if ($userWarning === null) {
            $this->telegram->sendMessage(
                $command->chatId,
                '🤔 Пользователя нет в списке предупреждений'
            );
            return;
        }

        $this->userWarningRepository->remove($userWarning);

        $this->telegram->sendMessage(
            $command->chatId,
            'Пользователь успешно убран из списка предупреждений'
        );
    }
}
