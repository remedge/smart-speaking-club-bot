<?php

declare(strict_types=1);

namespace App\BlockedUser\Infrastructure\Doctrine\Repository;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\User\Domain\User;
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

    public function findByUsername(string $username): ?BlockedUser
    {
        $result = $this->createQueryBuilder('bu')
            ->join(User::class, 'u', 'WITH', 'bu.userId = u.id')
            ->andWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getResult();

        return current($result) ?: null;
    }

    public function findByUserId(UuidInterface $userId): ?BlockedUser
    {
        $result = $this->createQueryBuilder('ub')
            ->andWhere('ub.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return current($result) ?: null;
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('bu')
            ->select('bu.id, bu.userId, u.username, u.chatId, u.firstName, u.lastName')
            ->join(User::class, 'u', 'WITH', 'bu.userId = u.id')
            ->orderBy('bu.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
