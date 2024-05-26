<?php

declare(strict_types=1);

namespace App\BlockedUser\Infrastructure\Doctrine\Repository;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<BlockedUser>
 */
class DoctrineBlockedUserRepository extends ServiceEntityRepository implements BlockedUserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockedUser::class);
    }

    public function save(BlockedUser $blockedUser): void
    {
        $this->_em->persist($blockedUser);
        $this->_em->flush();
    }

    public function remove(BlockedUser $blockedUser): void
    {
        $this->_em->remove($blockedUser);
        $this->_em->flush();
    }

    public function findById(UuidInterface $id): ?BlockedUser
    {
        return $this->find($id);
    }

    public function findByUserId(UuidInterface $userId, DateTimeImmutable $now): ?BlockedUser
    {
//        $result = $this->createQueryBuilder('ub')
//            ->andWhere('ub.userId = :userId')
//            ->andWhere('ub.endDate > :now')
//            ->setParameter('userId', $userId)
//            ->setParameter('now', $now)
//            ->orderBy('ub.endDate', 'desc')
//            ->getQuery()
//            ->getResult();
//
//        return current($result) ?: null;
    }

    public function findAll(): ?array
    {
//        return $this->createQueryBuilder('ub')
//            ->select('ub.id, u.username, u.chatId, u.firstName, u.lastName, ub.endDate')
//            ->join(User::class, 'u', 'WITH', 'ub.userId = u.id')
//            ->getQuery()
//            ->getResult();
    }
}
