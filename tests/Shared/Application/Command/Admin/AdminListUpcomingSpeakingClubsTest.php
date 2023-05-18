<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class AdminListUpcomingSpeakingClubsTest extends BaseApplicationTest
{
    public function testEmpty(): void
    {
        $this->sendWebhookCommand(666666, 'admin_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals('Пока не запланировано ни одного клуба', $message['text']);
        self::assertEquals([], $message['replyMarkup']);
    }

    public function testSuccess(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test club 1',
            description: 'Test description 1',
            maxParticipantsCount: 11,
            date: new DateTimeImmutable('2000-01-01 11:11'),
        ));
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            name: 'Test club 2',
            description: 'Test description 2',
            maxParticipantsCount: 12,
            date: new DateTimeImmutable('2000-01-02 22:22'),
        ));
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000003'),
            name: 'Test club 3',
            description: 'Test description 3',
            maxParticipantsCount: 13,
            date: new DateTimeImmutable('2000-01-03 03:03'),
            isCancelled: true
        ));
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000004'),
            name: 'Test club 4',
            description: 'Test description 4',
            maxParticipantsCount: 14,
            date: new DateTimeImmutable('1999-01-04 04:04'),
        ));

        $this->sendWebhookCommand(666666, 'admin_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals('Список ближайших разговорных клубов и других мероприятий школы. Нажмите на один из них, чтобы увидеть подробную информацию', $message['text']);
        self::assertEquals([
            [[
                'text' => '01.01 11:11 - Test club 1',
                'callback_data' => 'admin_show_speaking_club:00000000-0000-0000-0000-000000000001',
            ]],
            [[
                'text' => '02.01 22:22 - Test club 2',
                'callback_data' => 'admin_show_speaking_club:00000000-0000-0000-0000-000000000002',
            ]],
        ], $message['replyMarkup']);
    }
}
