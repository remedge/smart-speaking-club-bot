<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Query;

use App\SpeakingClub\Application\DTO\SpeakingClubDTO;
use App\SpeakingClub\Application\Exception\SpeakingClubNotFoundException;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Ramsey\Uuid\UuidInterface;

class SpeakingClubQuery
{
    public function __construct(
        public SpeakingClubRepository $speakingClubRepository,
    ) {
    }

    public function getById(UuidInterface $id): SpeakingClubDTO
    {
        $speakingClub = $this->speakingClubRepository->findById($id);
        if ($speakingClub === null) {
            throw new SpeakingClubNotFoundException($id);
        }

        return new SpeakingClubDTO(
            id: $speakingClub->getId(),
            name: $speakingClub->getName(),
            date: $speakingClub->getDate(),
        );
    }
}
