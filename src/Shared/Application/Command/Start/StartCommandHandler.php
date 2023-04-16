<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Start;

use App\Shared\Domain\TelegramInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class StartCommandHandler
{
    public function __construct(
        private readonly TelegramInterface $telegram,
    )
    {
    }

    public function __invoke(StartCommand $command): void
    {
        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text:  'Hello world'
        );
    }
}