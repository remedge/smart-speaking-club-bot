<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Start;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Longman\TelegramBot\Request;

#[AsMessageHandler]
class StartCommandHandler
{
    public function __invoke(StartCommand $command): void
    {
        Request::sendMessage([
            'chat_id' => $command->chatId,
            'text' => 'Hello world',
        ]);
    }
}