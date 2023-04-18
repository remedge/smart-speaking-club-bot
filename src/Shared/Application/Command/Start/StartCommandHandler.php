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
    ) {
    }

    public function __invoke(StartCommand $command): void
    {
        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: '🤖 Телеграм-бот для записи на разговорные клубы 🎙️

🌟 Описание:
Привет! Я - умный телеграм-бот, созданный для облегчения записи на разговорные клубы. Мой основной задачей является помочь тебе найти подходящий разговорный клуб, зарегистрироваться на удобную для тебя дату и время, а также напоминать о предстоящих встречах. Независимо от того, хочешь ли ты улучшить свои навыки иностранного языка, найти новых друзей или просто провести время с пользой - я здесь, чтобы помочь!'
        );
    }
}
