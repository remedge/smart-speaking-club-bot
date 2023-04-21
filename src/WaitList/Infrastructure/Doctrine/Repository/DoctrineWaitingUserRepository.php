<?php

declare(strict_types=1);

namespace App\WaitList\Infrastructure\Doctrine\Repository;

use App\User\Domain\User;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<WaitingUser>
 */
class DoctrineWaitingUserRepository extends ServiceEntityRepository implements WaitingUserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaitingUser::class);
    }

    public function save(WaitingUser $waitingUser): void
    {
        $this->_em->persist($waitingUser);
        $this->_em->flush();
    }

    public function remove(WaitingUser $waitingUser): void
    {
        $this->_em->remove($waitingUser);
        $this->_em->flush();
    }

    public function findOneByUserIdAndSpeakingClubId(UuidInterface $userId, UuidInterface $speakingClubId): ?array
    {
        return $this->createQueryBuilder('wu')
            ->select('wu.id, wu.userId, wu.speakingClubId, u.chatId')
            ->join(User::class, 'u', 'WITH', 'wu.userId = u.id')
            ->where('wu.userId = :userId')
            ->andWhere('wu.speakingClubId = :speakingClubId')
            ->setParameter('userId', $userId)
            ->setParameter('speakingClubId', $speakingClubId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySpeakingClubId(UuidInterface $speakingClubId): array
    {
        return $this->createQueryBuilder('wu')
            ->select('wu.id, wu.userId, wu.speakingClubId, u.chatId')
            ->join(User::class, 'u', 'WITH', 'wu.userId = u.id')
            ->where('wu.speakingClubId = :speakingClubId')
            ->setParameter('speakingClubId', $speakingClubId)
            ->getQuery()
            ->getResult();
    }

    public function findById(UuidInterface $id): ?WaitingUser
    {
        return $this->find($id);
    }
}
