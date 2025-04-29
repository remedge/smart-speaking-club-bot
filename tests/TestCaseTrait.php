<?php

namespace App\Tests;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\UserBan\Domain\UserBan;
use App\UserBan\Domain\UserBanRepository;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait TestCaseTrait
{
    /**
     * @throws Exception
     */
    protected function createSpeakingClub(
        string $name = 'Test Club',
        string $date = null,
        int $minParticipantsCount = 5,
        int $maxParticipantsCount = 10,
        bool $isCancelled = false,
        string $link = null,
        string $teacherUsername = null
    ): SpeakingClub {
        $date = $date ?: date('Y-m-d H:i:s', timestamp: strtotime('+1 hour'));
        $speakingClub = new SpeakingClub(
            id: $this->uuidProvider->provide(),
            name: $name,
            description: 'Test Description',
            minParticipantsCount: $minParticipantsCount,
            maxParticipantsCount: $maxParticipantsCount,
            date: new DateTimeImmutable($date),
            isCancelled: $isCancelled,
            link: $link,
            teacherUsername: $teacherUsername
        );

        /** @var SpeakingClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(SpeakingClubRepository::class);
        $clubRepository->save($speakingClub);

        return $speakingClub;
    }

    protected function createParticipation(
        UuidInterface $speakingClubId,
        string $userId,
        bool $isPlusOne = false,
    ): Participation {
        $participation = new Participation(
            id: $this->uuidProvider->provide(),
            userId: Uuid::fromString($userId),
            speakingClubId: $speakingClubId,
            isPlusOne: $isPlusOne,
        );

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participationRepository->save($participation);

        return $participation;
    }

    /**
     * @throws Exception
     */
    protected function createBannedUser(UuidInterface $userId, DateTimeImmutable $endDatetime = null): UserBan
    {
        $userBan = new UserBan(
            id: $this->uuidProvider->provide(),
            userId: $userId,
            endDate: $endDatetime ?: new DateTimeImmutable('+1 minute'),
            createdAt: new DateTimeImmutable()
        );

        /** @var UserBanRepository $userBanRepository */
        $userBanRepository = self::getContainer()->get(UserBanRepository::class);
        $userBanRepository->save($userBan);

        return $userBan;
    }

    protected function createBlockedUser(UuidInterface $userId): BlockedUser
    {
        $blockedUser = new BlockedUser(
            id: $this->uuidProvider->provide(),
            userId: $userId,
            createdAt: new DateTimeImmutable()
        );

        /** @var BlockedUserRepository $blockedUserRepository */
        $blockedUserRepository = self::getContainer()->get(BlockedUserRepository::class);
        $blockedUserRepository->save($blockedUser);

        return $blockedUser;
    }
}