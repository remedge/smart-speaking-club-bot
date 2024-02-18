<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class AdminAddPlusOneToParticipantTest extends BaseApplicationTest
{
    public function testSuccess(): void
    {
        $speakingClub = $this->createSpeakingClub();

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(
            new Participation(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                isPlusOne: false
            )
        );

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Участнику добавлен +1', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => '<< Вернуться к списку участников',
                    'callback_data' => 'show_participants:00000000-0000-0000-0000-000000000001',
                ]
            ],
        ], $message['replyMarkup']);

        $message = $this->getFirstMessage(111111);
        self::assertEquals(
            sprintf(
                'Администратор добавил вам +1 к участию в клубе "%s" %s %s',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
            $message['text']
        );
        self::assertEquals([
            [
                [
                    'text'          => 'Посмотреть информацию о клубе',
                    'callback_data' => 'show_speaking_club:00000000-0000-0000-0000-000000000001',
                ]
            ],
        ], $message['replyMarkup']);

        $participation = $participationRepository->findById(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        self::assertTrue($participation->isPlusOne());
    }

    public function testParticipationNotFound(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('🤔 Участник не найден', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_admin_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testClubNotFound(): void
    {
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(
            new Participation(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                isPlusOne: false
            )
        );

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('🤔 Разговорный клуб не найден', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_admin_list',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testNoFreeSpace(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(
            new SpeakingClub(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                name: 'Test Club',
                description: 'Test Description',
                minParticipantsCount: 1,
                maxParticipantsCount: 1,
                date: new DateTimeImmutable('2021-01-01 12:00'),
            )
        );

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(
            new Participation(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                isPlusOne: false
            )
        );

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('В клубе нет свободных мест', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => '<< Вернуться к списку участников',
                    'callback_data' => 'show_participants:00000000-0000-0000-0000-000000000001',
                ]
            ],
        ], $message['replyMarkup']);
    }

    public function testAlreadyPlusOne(): void
    {
        $speakingClub = $this->createSpeakingClub();

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(
            new Participation(
                id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                userId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
                isPlusOne: true
            )
        );

        $this->sendWebhookCallbackQuery(666666, 123, 'admin_add_plus_one:00000000-0000-0000-0000-000000000001');
        $this->assertResponseIsSuccessful();

        $message = $this->getMessage(666666, 123);

        self::assertEquals('Участник уже имеет +1', $message['text']);
        self::assertEquals([
            [
                [
                    'text'          => '<< Вернуться к списку участников',
                    'callback_data' => 'show_participants:00000000-0000-0000-0000-000000000001',
                ]
            ],
        ], $message['replyMarkup']);
    }
}
