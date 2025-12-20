<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Query;

use App\SpeakingClub\Application\DTO\ParticipationDTO;
use App\SpeakingClub\Domain\ParticipationRepository;
use Ramsey\Uuid\UuidInterface;

class ParticipationQuery
{
    public function __construct(
        private ParticipationRepository $participationRepository,
    ) {
    }

    /**
     * @return array<ParticipationDTO>
     */
    public function findBySpeakingClubId(UuidInterface $speakingClubId): array
    {
        $participations = $this->participationRepository->findBySpeakingClubId($speakingClubId);
        $participationDTOs = [];
        foreach ($participations as $participation) {
            $participationDTOs[] = new ParticipationDTO(
                id: $participation['id'],
                username: $participation['username'],
                firstName: $participation['firstName'],
                lastName: $participation['lastName'],
                chatId: (int) $participation['chatId'],
                isPlusOne: $participation['isPlusOne'],
                plusOneName: $participation['plusOneName'] ?? null,
            );
        }

        return $participationDTOs;
    }
}
