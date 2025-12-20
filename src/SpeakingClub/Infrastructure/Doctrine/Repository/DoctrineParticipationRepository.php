<?php

declare(strict_types=1);

namespace App\SpeakingClub\Infrastructure\Doctrine\Repository;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\User\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class DoctrineParticipationRepository extends ServiceEntityRepository implements ParticipationRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function save(Participation $participation): void
    {
        $this->_em->persist($participation);
        $this->_em->flush();
    }

    public function remove(Participation $participation): void
    {
        $this->_em->remove($participation);
        $this->_em->flush();
    }

    public function isUserParticipantOfSpeakingClub(UuidInterface $userId, UuidInterface $speakingClubId): bool
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.userId = :userId')
            ->andWhere('p.speakingClubId = :speakingClubId')
            ->setParameter('userId', $userId)
            ->setParameter('speakingClubId', $speakingClubId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?Participation
    {
        return $this->findOneBy([
            'userId' => $userId,
            'speakingClubId' => $speakingClubId,
        ]);
    }

    public function countByClubId(UuidInterface $speakingClubId): int
    {
        $participants = $this->createQueryBuilder('p')
            ->select('p.id, p.isPlusOne')
            ->where('p.speakingClubId = :speakingClubId')
            ->setParameter('speakingClubId', $speakingClubId)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($participants as $participant) {
            if ($participant['isPlusOne'] === true) {
                $count += 2;
            } else {
                $count++;
            }
        }

        return $count;
    }

    public function findBySpeakingClubId(UuidInterface $speakingClubId): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, u.username, u.chatId, p.isPlusOne, u.firstName, u.lastName, p.plusOneName')
            ->join(User::class, 'u', 'WITH', 'p.userId = u.id')
            ->where('p.speakingClubId = :speakingClubId')
            ->setParameter('speakingClubId', $speakingClubId)
            ->getQuery()
            ->getArrayResult();
    }

    public function findById(UuidInterface $id): ?Participation
    {
        return $this->find($id);
    }
}
