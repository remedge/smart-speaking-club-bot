<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine\Fixtures;

use App\User\Domain\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class UserFixtures extends Fixture
{
    public const USER_ID_1 = '00000000-0000-0000-0000-000000000001';

    public const USER_ID_2 = '00000000-0000-0000-0000-000000000002';

    public const ADMIN_ID = '00000000-0000-0000-0000-000000000003';

    public function load(ObjectManager $manager): void
    {
        $manager->persist(new User(
            id: Uuid::fromString(self::USER_ID_1),
            chatId: 111111,
            firstName: 'John',
            lastName: 'Connnor',
            username: 'john_connor',
            createdAt: new DateTimeImmutable('2000-01-01 00:00'),
        ));
        $manager->persist(new User(
            id: Uuid::fromString(self::USER_ID_2),
            chatId: 222222,
            firstName: 'Sarah',
            lastName: 'Connor',
            username: 'sarah_connor',
            createdAt: new DateTimeImmutable('2000-01-01 00:00'),
        ));
        $manager->persist(new User(
            id: Uuid::fromString(self::ADMIN_ID),
            chatId: 666666,
            firstName: 'Kyle',
            lastName: 'Reese',
            username: 'kyle_reese',
            createdAt: new DateTimeImmutable('2000-01-01 00:00'),
        ));

        $manager->flush();
    }
}
