<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Repository;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<SpeakingClub>
 */
class DoctrineSpeakingClubRepository extends ServiceEntityRepository implements SpeakingClubRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpeakingClub::class);
    }

    public function save(SpeakingClub $speakingClub): void
    {
        $this->_em->persist($speakingClub);
        $this->_em->flush();
    }

    public function findAllUpcoming(DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('speaking_club')
            ->andWhere('speaking_club.date > :now')
            ->setParameter('now', $now)
            ->orderBy('speaking_club.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(UuidInterface $id): ?SpeakingClub
    {
        return $this->find($id);
    }

    public function findUserUpcoming(UuidInterface $userId, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('speaking_club')
            ->innerJoin(Participation::class, 'participation', 'WITH', 'participation.speakingClubId = speaking_club.id')
            ->andWhere('speaking_club.date > :now')
            ->andWhere('participation.userId = :user')
            ->setParameter('now', $now)
            ->setParameter('user', $userId)
            ->orderBy('speaking_club.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
