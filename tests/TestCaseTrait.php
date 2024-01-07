<?php

namespace App\Tests;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

trait TestCaseTrait
{
    /**
     * @throws Exception
     */
    protected function createSpeakingClub(
        string $id = '00000000-0000-0000-0000-000000000001',
        string $name = 'Test Club',
        string $date = '2021-01-01 12:00'
    ): SpeakingClub {
        $speakingClub = new SpeakingClub(
            id: Uuid::fromString($id),
            name: $name,
            description: 'Test Description',
            minParticipantsCount: 5,
            maxParticipantsCount: 10,
            date: new DateTimeImmutable($date),
        );

        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save($speakingClub);

        return $speakingClub;
    }

    protected function createParticipation(
        string $speakingClubId,
        string $id,
        string $userId,
        bool $isPlusOne = false,
    ): Participation {
        $participation = new Participation(
            id: Uuid::fromString($id),
            userId: Uuid::fromString($userId),
            speakingClubId: Uuid::fromString($speakingClubId),
            isPlusOne: $isPlusOne,
        );

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save($participation);

        return $participation;
    }
}