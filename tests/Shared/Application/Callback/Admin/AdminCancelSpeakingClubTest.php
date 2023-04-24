<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

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

class AdminCancelSpeakingClubTest extends BaseApplicationTest
{
    public function testSuccess(): void
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
            userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false
        ));

        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitingUserRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_2),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        ));

        $this->sendWebhookCallbackQuery(666666, 123, 'cancel_club:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Клуб "Test Club" 01.01.2021 12:00 успешно отменен', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(111111);

        self::assertEquals('К сожалению, клуб "Test Club" 01.01.2021 12:00 был отменен', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(222222);

        self::assertEquals('К сожалению, клуб "Test Club" 01.01.2021 12:00 был отменен', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'cancel_club:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Клуб не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }
}
