<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\User;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class ListUserUpcomingSpeakingClubsTest extends BaseApplicationTest
{
    public function testEmpty(): void
    {
        $this->sendWebhookCommand(111111, 'my_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals('Вы не записаны ни на один клуб. Выберите клуб из списка, чтобы записаться.', $message['text']);
        self::assertEquals([
            [[
                'text' => 'Перейти к списку ближайших клубов',
                'callback_data' => 'back_to_list',
            ]],
        ], $message['replyMarkup']);
    }

    public function testExistingUpcoming(): void
    {
        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            name: 'Test club 1',
            description: 'Test description 1',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2000-01-01 11:11'),
        ));
        $clubRepository->save(new SpeakingClub(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            name: 'Test club 2',
            description: 'Test description 2',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable('2000-01-02 22:22'),
            isCancelled: true
        ));

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            isPlusOne: false,
        ));
        $participationRepository->save(new Participation(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            userId: Uuid::fromString(UserFixtures::USER_ID_1),
            speakingClubId: Uuid::fromString('00000000-0000-0000-0000-000000000002'),
            isPlusOne: false,
        ));

        $this->sendWebhookCommand(111111, 'my_upcoming_clubs');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(111111);

        self::assertEquals('Список ближайших клубов, куда вы записаны:', $message['text']);
        self::assertEquals([
            [[
                'text' => '01.01 11:11 - Test club 1',
                'callback_data' => 'show_my_speaking_club:00000000-0000-0000-0000-000000000001',
            ]],
        ], $message['replyMarkup']);
    }
}
