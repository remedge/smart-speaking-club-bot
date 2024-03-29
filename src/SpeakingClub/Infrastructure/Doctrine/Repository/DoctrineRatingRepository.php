<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Repository;

use App\SpeakingClub\Domain\Rating;
use App\SpeakingClub\Domain\RatingRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\User\Domain\User;
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

    public function findAllNondumped(): array
    {
        return $this->createQueryBuilder('r')
            ->select('sc.name, sc.date, u.username, r.id, r.rating, r.comment')
            ->innerJoin(SpeakingClub::class, 'sc', 'WITH', 'r.speakingClubId = sc.id')
            ->innerJoin(User::class, 'u', 'WITH', 'r.userId = u.id')
            ->where('r.isDumped = false')
            ->getQuery()
            ->getResult();
    }

    public function markDumped(UuidInterface $ratingId): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.isDumped', true)
            ->where('r.id = :ratingId')
            ->setParameter('ratingId', $ratingId)
            ->getQuery()
            ->execute();
    }
}
