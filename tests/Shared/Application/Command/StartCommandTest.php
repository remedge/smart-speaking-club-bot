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

        self::assertEquals([
            [[
                'text' => '📅 Посмотреть расписание',
                'callback_data' => 'upcoming_clubs',
            ]],
            [[
                'text' => '💌 Посмотреть мои записи',
                'callback_data' => 'my_upcoming_clubs',
            ]],
        ], $message['replyMarkup']);
    }

    public function testAdmin(): void
    {
        $this->sendWebhookCommand(666666, 'start');
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(666666);

        self::assertEquals([
            [[
                'text' => '📅 Посмотреть расписание',
                'callback_data' => 'admin_upcoming_clubs',
            ]],
            [[
                'text' => '📝 Добавить новый разговорный клуб',
                'callback_data' => 'admin_create_club',
            ]],
            [[
                'text' => '📤 Отправить сообщение всем пользователям бота',
                'callback_data' => 'admin_send_message',
            ]],
        ], $message['replyMarkup']);
    }
}
