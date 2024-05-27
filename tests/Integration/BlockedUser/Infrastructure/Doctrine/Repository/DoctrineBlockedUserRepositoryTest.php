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
}
