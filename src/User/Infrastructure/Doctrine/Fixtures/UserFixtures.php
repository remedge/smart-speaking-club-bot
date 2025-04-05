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
    public const USER_ID_JOHN_CONNNOR = '00000000-0000-0000-0000-000000000001';
    public const USER_CHAT_ID_JOHN_CONNNOR = 111111;
    public const USER_USERNAME_JOHN_CONNNOR = 'john_connor';

    public const USER_ID_SARAH_CONNOR = '00000000-0000-0000-0000-000000000002';
    public const USER_CHAT_ID_SARAH_CONNOR = 222222;
    public const USER_USERNAME_SARAH_CONNOR = 'sarah_connor';

    public const ADMIN_ID = '00000000-0000-0000-0000-000000000003';
    public const ADMIN_CHAT_ID = 666666;
    public const ADMIN_USERNAME = 'kyle_reese';

    public function load(ObjectManager $manager): void
    {
        $manager->persist(new User(
            id: Uuid::fromString(self::USER_ID_JOHN_CONNNOR),
            chatId: self::USER_CHAT_ID_JOHN_CONNNOR,
            firstName: 'John',
            lastName: 'Connnor',
            username: 'john_connor',
            createdAt: new DateTimeImmutable('2000-01-01 00:00'),
        ));
        $manager->persist(new User(
            id: Uuid::fromString(self::USER_ID_SARAH_CONNOR),
            chatId: self::USER_CHAT_ID_SARAH_CONNOR,
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
