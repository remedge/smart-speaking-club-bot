<?php

declare(strict_types=1);

namespace App\WaitList\Application\Query;

use App\WaitList\Application\DTO\WaitingUserDTO;
use App\WaitList\Domain\WaitingUserRepository;
use Ramsey\Uuid\UuidInterface;

class WaitingUserQuery
{
    public function __construct(
        private WaitingUserRepository $waitListRepository,
    ) {
    }

    public function findByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?WaitingUserDTO
    {
        $waitingUser = $this->waitListRepository->findByUserIdAndSpeakingClubId($userId, $speakingClubId);

        if ($waitingUser === null) {
            return null;
        }

        return new WaitingUserDTO(
            id: $waitingUser->getId(),
            userId: $waitingUser->getUserId(),
            speakingClubId: $waitingUser->getSpeakingClubId(),
        );
    }

    /**
     * @return array<WaitingUserDTO>
     */
    public function findBySpeakingClubId(UuidInterface $speakingClubId): array
    {
        $waitingUsers = $this->waitListRepository->findBySpeakingClubId($speakingClubId);
        $waitingUsersDTO = [];
        foreach ($waitingUsers as $waitingUser) {
            $waitingUsersDTO[] = new WaitingUserDTO(
                id: $waitingUser->getId(),
                userId: $waitingUser->getUserId(),
                speakingClubId: $waitingUser->getSpeakingClubId(),
            );
        }

        return $waitingUsersDTO;
    }
}
