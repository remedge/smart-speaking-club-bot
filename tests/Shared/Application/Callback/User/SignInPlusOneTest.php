<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
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
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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
            UserFixtures::USER_ID_1
        );

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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
            userId: Uuid::fromString(UserFixtures::USER_ID_2),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );
        
        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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

        $userBan = $this->createBannedUser(Uuid::fromString(UserFixtures::USER_ID_1));

        $this->sendWebhookCallbackQuery(
            chatId: self::KYLE_REESE_CHAT_ID,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );

        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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
            Uuid::fromString(UserFixtures::USER_ID_1),
            (new DateTimeImmutable())->modify('+25 hours')
        );
        $userBan = $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_1),
            (new DateTimeImmutable())->modify('+2 days')
        );

        $this->sendWebhookCallbackQuery(
            chatId: self::KYLE_REESE_CHAT_ID,
            messageId: 123,
            callbackData: 'sign_in_plus_one:' . $speakingClub->getId()
        );

        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::KYLE_REESE_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::KYLE_REESE_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::KYLE_REESE_CHAT_ID, self::MESSAGE_ID);

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
