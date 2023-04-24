<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command;

use App\Tests\Shared\BaseApplicationTest;

class StartCommandTest extends BaseApplicationTest
{
    public function testUser(): void
    {
        $this->sendWebhookCommand(111111, 'start');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals(<<<HEREDOC
🤖 Телеграм-бот для записи на разговорные клубы 🎙️

🌟 Описание:
Привет! Я - умный телеграм-бот, созданный для облегчения записи на разговорные клубы. Мой основной задачей является помочь тебе найти подходящий разговорный клуб, зарегистрироваться на удобную для тебя дату и время, а также напоминать о предстоящих встречах. Независимо от того, хочешь ли ты улучшить свои навыки иностранного языка, найти новых друзей или просто провести время с пользой - я здесь, чтобы помочь!
HEREDOC, $message['text']);

        self::assertNull($message['replyMarkup']);
    }

    public function testAdmin(): void
    {
        $this->sendWebhookCommand(666666, 'start');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals(<<<HEREDOC
🤖 Телеграм-бот для записи на разговорные клубы 🎙️

🌟 Описание:
Привет! Я - умный телеграм-бот, созданный для облегчения записи на разговорные клубы. Мой основной задачей является помочь тебе найти подходящий разговорный клуб, зарегистрироваться на удобную для тебя дату и время, а также напоминать о предстоящих встречах. Независимо от того, хочешь ли ты улучшить свои навыки иностранного языка, найти новых друзей или просто провести время с пользой - я здесь, чтобы помочь!
HEREDOC, $message['text']);

        self::assertNull($message['replyMarkup']);
    }
}
