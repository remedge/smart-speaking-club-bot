<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Repository;

use App\SpeakingClub\Domain\Rating;
use App\SpeakingClub\Domain\RatingRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class DoctrineRatingRepository extends ServiceEntityRepository implements RatingRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function save(Rating $rating): void
    {
        $this->_em->persist($rating);
        $this->_em->flush();
    }

    public function findBySpeakingClubIdAndUserId(
        UuidInterface $speakingClubId,
        UuidInterface $userId,
    ): ?Rating {
        return $this->findOneBy([
            'speakingClubId' => $speakingClubId,
            'userId' => $userId,
        ]);
    }
}
