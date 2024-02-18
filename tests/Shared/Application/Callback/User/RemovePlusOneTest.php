<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class RemovePlusOneTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: true,
        ));

        /** @var WaitingUserRepository $waitlistRepository */
        $waitlistRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitlistRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_2),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'remove_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
👌Вы успешно убрали +1 человека с собой
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ваших клубов',
                'callback_data' => 'back_to_my_list',
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(222222);

        self::assertEquals(<<<HEREDOC
В клубе "Test Club" 01.01.2021 12:00 появилось свободное место. Перейдите к описанию клуба, чтобы записаться
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к описанию клуба',
                'callback_data' => 'show_speaking_club:00000000-0000-0000-0000-000000000001',
            ]],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'remove_plus_one:00000000-0000-0000-0000-000000000001'
        );
        
        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
🤔 Разговорный клуб не найден
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testNotSigned(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'remove_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
🤔 Вы не записаны на этот клуб
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testSignedWithoutPlusOne(): void
    {
        $speakingClub = $this->createSpeakingClub();

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'remove_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();

        $this->assertArrayHasKey(self::CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(self::CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(self::CHAT_ID, self::MESSAGE_ID);

        self::assertEquals(<<<HEREDOC
🤔 Вы не добавляли +1 с собой на этот клуб
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }
}
