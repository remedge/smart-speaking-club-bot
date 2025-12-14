<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\System\DateHelper;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class SignInPlusOneTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        
        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
👌 Вы успешно записаны на клуб c +1 человеком
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ваших клубов',
                'callback_data' => 'back_to_my_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in_plus_one:00000000-0000-0000-0000-000000000001'
        );
        
        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
🤔 Такой клуб не найден
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
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
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        
        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
🤔 Вы уже записаны на этот разговорный клуб
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testNoFreeSpace(): void
    {
        $speakingClub = $this->createSpeakingClub(minParticipantsCount: 1, maxParticipantsCount: 1);

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        
        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
😔 К сожалению, все свободные места на данный клуб заняты
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Встать в лист ожидания',
                'callback_data' => 'join_waiting_list:' . $speakingClub->getId()
            ]],
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
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
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
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
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
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
    public function testMaxClubsReached(): void
    {
        $speakingClub = $this->createSpeakingClub();

        // Создаем 5 участий для пользователя
        $userClubs = [];
        for ($i = 0; $i < 5; $i++) {
            $club = $this->createSpeakingClub(
                name: 'Test Club ' . ($i + 1),
                date: date('Y-m-d H:i:s', strtotime('+' . ($i + 1) . ' day'))
            );
            $userClubs[] = $club;
            $this->createParticipation(
                $club->getId(),
                UserFixtures::USER_ID_JOHN_CONNNOR
            );
        }

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        self::assertEquals(
            "Кажется, ваш календарь переполнен! 📅\n\nВы записаны сразу на 5 клубов вперед. Чтобы добавить шестой, нужно завершить одно из занятий или отменить менее важную бронь.\n\nТак мы даем шанс попасть на практику всем желающим. Спасибо за понимание! ❤️\n\nКакую запись отменим?",
            $message['text']
        );

        $expectedButtons = [];
        foreach ($userClubs as $club) {
            $expectedButtons[] = [
                [
                    'text'          => sprintf(
                        '%s - %s',
                        $club->getDate()->format('d.m H:i') . ' ' . DateHelper::getDayOfTheWeek(
                            $club->getDate()->format('d.m.Y')
                        ),
                        $club->getName()
                    ),
                    'callback_data' => 'show_my_speaking_club:' . $club->getId(),
                ]
            ];
        }

        self::assertEquals($expectedButtons, $message['replyMarkup']);
    }

    /**
     * @throws Exception
     */
    public function testMaxClubsReachedIgnoresPastClubs(): void
    {
        $speakingClub = $this->createSpeakingClub();

        // Создаем 3 прошедших клуба
        for ($i = 1; $i <= 3; $i++) {
            $pastClub = $this->createSpeakingClub(
                name: 'Past Club ' . $i,
                date: date('Y-m-d H:i:s', strtotime('-' . $i . ' day'))
            );
            $this->createParticipation(
                $pastClub->getId(),
                UserFixtures::USER_ID_JOHN_CONNNOR
            );
        }

        // Создаем 4 будущих клуба (всего 7, но будущих только 4)
        $userClubs = [];
        for ($i = 1; $i <= 4; $i++) {
            $club = $this->createSpeakingClub(
                name: 'Future Club ' . $i,
                date: date('Y-m-d H:i:s', strtotime('+' . $i . ' day'))
            );
            $userClubs[] = $club;
            $this->createParticipation(
                $club->getId(),
                UserFixtures::USER_ID_JOHN_CONNNOR
            );
        }

        // Пытаемся записаться на еще один клуб - должно получиться, так как будущих только 4
        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        // Должно быть сообщение об успешной записи, а не об ошибке лимита
        self::assertStringContainsString(
            '👌 Вы успешно записаны на клуб c +1 человеком',
            $message['text']
        );
    }

    /**
     * @throws Exception
     */
    public function testNoFreeSpaceTakesPriorityOverMaxClubs(): void
    {
        // Создаем клуб с 1 местом
        $speakingClub = $this->createSpeakingClub(minParticipantsCount: 1, maxParticipantsCount: 1);

        // Занимаем это место другим пользователем
        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_SARAH_CONNOR
        );

        // Создаем 5 участий для пользователя
        for ($i = 0; $i < 5; $i++) {
            $club = $this->createSpeakingClub(
                name: 'Test Club ' . ($i + 1),
                date: date('Y-m-d H:i:s', strtotime('+' . ($i + 1) . ' day'))
            );
            $this->createParticipation(
                $club->getId(),
                UserFixtures::USER_ID_JOHN_CONNNOR
            );
        }

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        // Должно быть сообщение о занятых местах, а не о лимите в 5 клубов
        self::assertEquals(
            '😔 К сожалению, все свободные места на данный клуб заняты',
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
    public function testWaitingListDoesNotCountAsParticipation(): void
    {
        $speakingClub = $this->createSpeakingClub();

        // Создаем 4 участия для пользователя
        for ($i = 0; $i < 4; $i++) {
            $club = $this->createSpeakingClub(
                name: 'Test Club ' . ($i + 1),
                date: date('Y-m-d H:i:s', strtotime('+' . ($i + 1) . ' day'))
            );
            $this->createParticipation(
                $club->getId(),
                UserFixtures::USER_ID_JOHN_CONNNOR
            );
        }

        // Добавляем пользователя в лист ожидания для 2 других клубов
        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        for ($i = 0; $i < 2; $i++) {
            $waitingClub = $this->createSpeakingClub(
                name: 'Waiting Club ' . ($i + 1),
                date: date('Y-m-d H:i:s', strtotime('+' . ($i + 5) . ' day'))
            );
            $waitingUserRepository->save(
                new WaitingUser(
                    id: $this->uuidProvider->provide(),
                    userId: Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
                    speakingClubId: $waitingClub->getId(),
                )
            );
        }

        // Пытаемся записаться на 5-й клуб с +1 - должно получиться, так как лист ожидания не считается
        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR, self::MESSAGE_ID);

        // Должно быть сообщение об успешной записи, а не об ошибке лимита
        self::assertStringContainsString(
            '👌 Вы успешно записаны на клуб c +1 человеком',
            $message['text']
        );
    }
}
