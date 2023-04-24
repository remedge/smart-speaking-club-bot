<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class AddPlusOneTest extends BaseApplicationTest
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

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
👌 Вы успешно добавили +1 человека с собой
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ваших клубов',
                'callback_data' => 'back_to_my_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
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

    public function testNotSigned(): void
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
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

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

    public function testSignedPlusOne(): void
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
            isPlusOne: true,
        ));

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
🤔 Вы уже добавили +1 с собой на этот клуб
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testNoFreeSpace(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test Club',
            description: 'Test Description',
            maxParticipantsCount: 1,
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

        $this->sendWebhookCallbackQuery(
            chatId: 111111,
            messageId: 123,
            callbackData: 'add_plus_one:00000000-0000-0000-0000-000000000001'
        );
        $this->assertResponseIsSuccessful();
        $message = $this->getMessage(111111, 123);

        self::assertEquals(<<<HEREDOC
😔 К сожалению, все свободные места на данный клуб заняты и вы не можете добавить +1
HEREDOC, $message['text']);

        self::assertEquals([
            [[
                'text' => 'Перейти к списку ваших клубов',
                'callback_data' => 'back_to_my_list',
            ]],
        ], $message['replyMarkup']);
    }
}
