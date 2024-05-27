<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command;

use App\Tests\Shared\BaseApplicationTest;

class HelpCommandTest extends BaseApplicationTest
{
    public function testUser(): void
    {
        $this->sendWebhookCommand(111111, 'help');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals(
            <<<HEREDOC
Список команд:
/start - Начать работу с ботом
/help - Показать список команд
/upcoming_clubs - Список клубов, которые будут в ближайшее время
/my_upcoming_clubs - Список клубов, в которых вы будете участвовать

HEREDOC,
            $message['text']
        );

        self::assertNull($message['replyMarkup']);
    }

    public function testAdmin(): void
    {
        $this->sendWebhookCommand(666666, 'help');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals(
            <<<HEREDOC
Список команд администратора:
/start - Начать работу с ботом
/help - Показать список команд
/admin_upcoming_clubs - Список клубов, которые будут в ближайшее время
/admin_create_club - Создать новый разговорный клуб
/bans - Список забаненных пользователей
/block_user - Заблокировать пользователя

HEREDOC,
            $message['text']
        );

        self::assertNull($message['replyMarkup']);
    }
}
