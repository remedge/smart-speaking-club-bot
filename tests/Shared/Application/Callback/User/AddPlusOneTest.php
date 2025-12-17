<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
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
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
Пожалуйста, укажите имя второго участника (+1):
HEREDOC,
            $message['text']
        );

        self::assertEquals([], $message['replyMarkup']);

        // Проверяем, что пользователь находится в состоянии ожидания имени +1
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        self::assertNotNull($user);
        self::assertEquals('RECEIVING_PLUS_ONE_NAME', $user->getState()->value);
        self::assertEquals($speakingClub->getId()->toString(), $user->getActualSpeakingClubData()['speakingClubId']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

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
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

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
            UserFixtures::USER_ID_JOHN_CONNNOR,
            true
        );

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

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
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'add_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

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
