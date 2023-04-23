<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command;

use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class UpcomingClubsCommandTest extends BaseApplicationTest
{
    public function testEmpty(): void
    {
        $this->sendWebhookRequest(111111, 'upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals('Пока мы не запланировали ни одного клуба. Попробуйте позже.', $message['text']);
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

        $this->sendWebhookRequest(111111, 'upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals('Список ближайших клубов:', $message['text']);
        self::assertEquals([
            [
                [
                    'text' => '01.01.2000 11:11 - Test club 1',
                    'callback_data' => 'show_speaking_club:00000000-0000-0000-0000-000000000001',
                ],
            ],
            [
                [
                    'text' => '02.01.2000 22:22 - Test club 2',
                    'callback_data' => 'show_speaking_club:00000000-0000-0000-0000-000000000002',
                ],
            ],
        ], $message['replyMarkup']);
    }
}
