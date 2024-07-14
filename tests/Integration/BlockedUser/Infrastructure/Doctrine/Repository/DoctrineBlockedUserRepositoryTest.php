<?php

declare(strict_types=1);

namespace App\Tests\Integration\BlockedUser\Infrastructure\Doctrine\Repository;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Infrastructure\Doctrine\Repository\DoctrineBlockedUserRepository;
use App\Shared\Application\Clock;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Ramsey\Uuid\Uuid;

class DoctrineBlockedUserRepositoryTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSave(): void
    {
        $clock = $this->getContainer()->get(Clock::class);

        $blockedUser = new BlockedUser(
            id: Uuid::fromString('00000000-0000-0000-0000-000000000001'),
            userId: Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            createdAt: $clock->now(),
        );
        /** @var DoctrineBlockedUserRepository $blockedUserRepository */
        $blockedUserRepository = self::getContainer()->get(DoctrineBlockedUserRepository::class);
        $blockedUserRepository->save($blockedUser);

        $savedBlockedUser = $blockedUserRepository->findById($blockedUser->getId());

        $this->assertNotNull($savedBlockedUser);

        $this->assertSame($blockedUser->getId(), $savedBlockedUser->getId());
        $this->assertSame($blockedUser->getUserId(), $savedBlockedUser->getUserId());
    }

    /**
     * @throws Exception
     */
    public function testFindAll(): void
    {
        $blockedUser1 = $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR)
        );
        $blockedUser2 = $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR)
        );

        /** @var DoctrineBlockedUserRepository $blockedUserRepository */
        $blockedUserRepository = self::getContainer()->get(DoctrineBlockedUserRepository::class);
        $blockedUsers = $blockedUserRepository->findAll();

        $this->assertCount(2, $blockedUsers);

        $this->assertSame($blockedUser2->getId()->toString(), $blockedUsers[0]['blocked_user_id']->toString());
        $this->assertSame($blockedUser1->getId()->toString(), $blockedUsers[1]['blocked_user_id']->toString());
    }

    public function testFindByUserId(): void
    {
        $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR)
        );
        $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR)
        );

        /** @var DoctrineBlockedUserRepository $blockedUserRepository */
        $blockedUserRepository = self::getContainer()->get(DoctrineBlockedUserRepository::class);
        $blockedUser = $blockedUserRepository->findByUserId(Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR));

        $this->assertSame(UserFixtures::USER_ID_SARAH_CONNOR, $blockedUser->getUserId()->toString());
    }
}
