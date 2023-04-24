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

class SignOutTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
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
            callbackData: 'sign_out:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
👌 Вы успешно отписаны от разговорного клуба
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
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
            callbackData: 'sign_out:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
🤔 Разговорный клуб не найден
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testAlreadySignedOut(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2021-01-01 12:00'),
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'sign_out:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
🤔 Вы не записаны на этот клуб
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }
}
