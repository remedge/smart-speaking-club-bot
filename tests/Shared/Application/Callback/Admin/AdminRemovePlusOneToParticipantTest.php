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

class AdminRemovePlusOneToParticipantTest extends BaseApplicationTest
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
            isPlusOne: true,
        ));

        /** @var WaitingUserRepository $waitingUserRepository */
        $waitingUserRepository = self::getContainer()->get(WaitingUserRepository::class);
        $waitingUserRepository->save(new WaitingUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_2),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
        ));

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_remove_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $participation = $participationRepository->findById(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        self::assertFalse($participation->isPlusOne());

        $message = $this->getMessage(666666, 123);

        self::assertEquals('У участника убран +1', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку участников',
                'callback_data' => 'show_participants:00000000-0000-0000-0000-000000000001',
            ]],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(222222);
        self::assertEquals('Появилось свободное место в клубе "Test Club" 01.01.2021 12:00, спешите записаться!', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Посмотреть информацию о клубе',
                'callback_data' => 'show_speaking_club:00000000-0000-0000-0000-000000000001',
            ]],
        ], $message['replyMarkup']);
    }

    public function testUnknownParticipant(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'admin_remove_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(666666);

        self::assertEquals('Участник не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testUnknownClub(): void
    {
        $this->sendWebhookCallbackQuery(666666, 123, 'admin_remove_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getFirstMessage(666666);

        self::assertEquals('Участник не найден', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => 'back_to_admin_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testNoPlusOne(): void
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

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_remove_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('У участника уже нет +1', $message['text']);
        self::assertEquals([
            [[
                'text' => '<< Вернуться к списку участников',
                'callback_data' => 'show_participants:00000000-0000-0000-0000-000000000001',
            ]],
        ], $message['replyMarkup']);
    }
}
