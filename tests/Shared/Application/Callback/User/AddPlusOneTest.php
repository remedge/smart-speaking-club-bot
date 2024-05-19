<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;

class AddPlusOneTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
👌 Вы успешно добавили +1 человека с собой
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Разговорный клуб не найден
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testNotSigned(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Вы не записаны на этот клуб
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testSignedPlusOne(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1,
            true
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Вы уже добавили +1 с собой на этот клуб
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testNoFreeSpace(): void
    {
        $speakingClub = $this->createSpeakingClub(minParticipantsCount: 1, maxParticipantsCount: 1);

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_1
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
😔 К сожалению, все свободные места на данный клуб заняты и вы не можете добавить +1
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ]
            ],
        ], $message['replyMarkup']);
    }
}
