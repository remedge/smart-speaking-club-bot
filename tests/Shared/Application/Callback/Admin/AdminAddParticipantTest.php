<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

use App\Tests\Shared\BaseApplicationTest;
use Exception;

class AdminAddParticipantTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_participant:' . $speakingClub->getId());
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Введите username участника, которого хотите добавить в разговорный клуб', $message['text']);
        self::assertEquals([], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_participant:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('🤔 Разговорный клуб не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Вернуться к списку',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }
}
