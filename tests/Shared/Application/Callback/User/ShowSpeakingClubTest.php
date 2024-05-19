<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;

class ShowSpeakingClubTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testShowClubWithNoParticipation(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:' . $speakingClub->getId());
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            sprintf(
                'Название: %s
Описание: Test Description
Дата: %s %s
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 0

Вы не записаны
',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Записаться',
                    'callback_data' => 'sign_in:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => 'Записаться с +1 человеком',
                    'callback_data' => 'sign_in_plus_one:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testShowClubWithSingleParticipation(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1
        );

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:' . $speakingClub->getId());
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            sprintf(
                'Название: %s
Описание: Test Description
Дата: %s %s
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 1

Вы записаны
',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Отменить запись',
                    'callback_data' => 'sign_out:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => 'Добавить +1 человека с собой',
                    'callback_data' => 'add_plus_one:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testShowClubWithPlusOneParticipation(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1,
            true
        );

        $this->sendWebhookCallbackQuery(111111, 123, 'show_speaking_club:' . $speakingClub->getId());

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            sprintf(
                'Название: %s
Описание: Test Description
Дата: %s %s
Минимальное количество участников: 5
Максимальное количество участников: 10
Записалось участников: 2

Вы записаны с +1 человеком
',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Отменить запись',
                    'callback_data' => 'sign_out:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => 'Убрать +1 человека с собой',
                    'callback_data' => 'remove_plus_one:' . $speakingClub->getId(),
                ]
            ],
            [
                [
                    'text'          => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }
}
