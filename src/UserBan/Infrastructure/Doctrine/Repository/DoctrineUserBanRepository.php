<?php

declare(strict_types=1);

namespace App\UserBan\Infrastructure\Doctrine\Repository;

use App\User\Domain\User;
use App\UserBan\Domain\UserBan;
use App\UserBan\Domain\UserBanRepository;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<UserBan>
 */
class DoctrineUserBanRepository extends ServiceEntityRepository implements UserBanRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBan::class);
    }

    public function save(UserBan $userBan): void
    {
        $this->_em->persist($userBan);
        $this->_em->flush();
    }

    public function remove(UserBan $userBan): void
    {
        $this->_em->remove($userBan);
        $this->_em->flush();
    }

    public function findById(UuidInterface $id): ?UserBan
    {
        return $this->find($id);
    }

    public function findByUserId(UuidInterface $userId, DateTimeImmutable $now): ?UserBan
    {
        $result = $this->createQueryBuilder('ub')
            ->andWhere('ub.userId = :userId')
            ->andWhere('ub.endDate > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', $now)
            ->orderBy('ub.endDate', 'desc')
            ->getQuery()
            ->getResult();

        return current($result) ?: null;
    }

    public function findAllBan(): ?array
    {
        return $this->createQueryBuilder('ub')
            ->select('ub.id, u.username, u.chatId, u.firstName, u.lastName, ub.endDate')
            ->join(User::class, 'u', 'WITH', 'ub.userId = u.id')
            ->getQuery()
            ->getResult();
    }

    public function findAllBanNow(DateTimeImmutable $now): ?array
    {
        return $this->createQueryBuilder('ub')
            ->select('ub.id, u.username, u.chatId, u.firstName, u.lastName, ub.endDate')
            ->join(User::class, 'u', 'WITH', 'ub.userId = u.id')
            ->andWhere('ub.endDate > :now')
            ->orderBy('ub.endDate', 'desc')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
}
