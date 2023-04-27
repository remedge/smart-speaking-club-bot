<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine\Repository;

use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByChatId(int $chatId): ?User
    {
        return parent::findOneBy([
            'chatId' => $chatId,
        ]);
    }

    public function findById(UuidInterface $id): ?User
    {
        return parent::findOneBy([
            'id' => $id,
        ]);
    }

    public function findByUsername(string $username): ?User
    {
        return parent::findOneBy([
            'username' => $username,
        ]);
    }

    public function findAllExceptUsernames(array $usernames): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username NOT IN (:usernames)')
            ->setParameter('usernames', $usernames)
            ->getQuery()
            ->getResult();
    }
}
