<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminListBanTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            new DateTimeImmutable('-1 minute')
        );

        $this->sendWebhookCommand(666666, 'bans');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        self::assertEquals('Никто не забанен', $message['text']);
        self::assertEquals(
            [
                [
                    [
                        'text'          => 'Забанить участника',
                        'callback_data' => 'add_ban',
                    ]
                ],
            ],
            $message['replyMarkup']
        );
    }

    /**
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            new DateTimeImmutable('-1 minute')
        );
        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            new DateTimeImmutable('+3 days')
        );
        $this->createBannedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR),
            new DateTimeImmutable('+2 hours')
        );

        $this->sendWebhookCommand(666666, 'bans');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(666666);

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user1 = $userRepository->findById(Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR));
        $user2 = $userRepository->findById(Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR));

        self::assertEquals('Список забаненных участников. Вы можете добавить или убрать участника', $message['text']);
        self::assertEquals(
            [
                [
                    [
                        'text'          => sprintf(
                            '%s %s (@%s) - Убрать',
                            $user1->getFirstName(),
                            $user1->getLastName(),
                            $user1->getUsername()
                        ),
                        'callback_data' => sprintf('remove_ban:%s', UserFixtures::USER_ID_JOHN_CONNNOR),
                    ]
                ],
                [
                    [
                        'text'          => sprintf(
                            '%s %s (@%s) - Убрать',
                            $user2->getFirstName(),
                            $user2->getLastName(),
                            $user2->getUsername()
                        ),
                        'callback_data' => sprintf('remove_ban:%s', UserFixtures::USER_ID_SARAH_CONNOR),
                    ]
                ],
                [
                    [
                        'text'          => 'Забанить участника',
                        'callback_data' => 'add_ban',
                    ]
                ],
            ],
            $message['replyMarkup']
        );
    }
}
