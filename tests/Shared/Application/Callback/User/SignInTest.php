<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class SignInTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            new DateTimeImmutable('-1 minute')
        );

        /** @var WaitingUserRepository $waitUserRepository */
        $waitUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitUserRepository->save(
            new WaitingUser(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
                speakingClubId: $speakingClub->getId(),
            )
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(
            sprintf(
                '👌 Вы успешно записаны на разговорный клуб "%s", который состоится %s в %s',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ]
            ],
        ], $message['replyMarkup']);

        $waitUser = $waitUserRepository->findOneByUserIdAndSpeakingClubId(
            userId: Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        );
        self::assertNull($waitUser);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in:00000000-0000-0000-0000-000000000001'
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

    /**
     * @throws Exception
     */
    public function testAlreadySigned(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
🤔 Вы уже записаны на этот разговорный клуб
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
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
            UserFixtures::USER_ID_SARAH_CONNOR
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(
            <<<HEREDOC
😔 К сожалению, все свободные места на данный клуб заняты
HEREDOC,
            $message['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => 'Встать в лист ожидания',
                    'callback_data' => 'join_waiting_list:' . $speakingClub->getId()
                ]
            ],
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testBannedUser(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $userBan = $this->createBannedUser(Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR));

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in:' . $speakingClub->getId()
        );

        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertStringContainsString(
            sprintf(
                'Здравствуйте! Мы заметили, что недавно вы дважды отменили участие в нашем разговорном клубе менее чем за 24 часа до начала. 

Чтобы гарантировать комфортное общение и планирование для всех участников, мы временно ограничиваем вашу возможность записываться на новые сессии. Это ограничение будет действовать до %s',
                $userBan->getEndDate()->format('d.m.Y H:i')
            ),
            $message['text']
        );
    }

    /**
     * @throws Exception
     */
    public function testDuplicatedBannedUser(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            (new DateTimeImmutable())->modify('+25 hours')
        );
        $userBan = $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            (new DateTimeImmutable())->modify('+2 days')
        );

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in:' . $speakingClub->getId()
        );

        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertStringContainsString(
            sprintf(
                'Здравствуйте! Мы заметили, что недавно вы дважды отменили участие в нашем разговорном клубе менее чем за 24 часа до начала. 

Чтобы гарантировать комфортное общение и планирование для всех участников, мы временно ограничиваем вашу возможность записываться на новые сессии. Это ограничение будет действовать до %s',
                $userBan->getEndDate()->format('d.m.Y H:i')
            ),
            $message['text']
        );
    }
}
