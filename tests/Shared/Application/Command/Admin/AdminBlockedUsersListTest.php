<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\Admin;

use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminBlockedUsersListTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testGetListWhenListIsEmpty(): void
    {
        $this->sendWebhookCommand(UserFixtures::ADMIN_CHAT_ID, 'blocked_users');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals('Список заблокированных пользователей пуст', $message['text']);
        self::assertEquals(
            [
                [
                    [
                        'text'          => 'Заблокировать участника',
                        'callback_data' => 'block_user',
                    ]
                ],
            ],
            $message['replyMarkup']
        );
    }

    public function testGetList(): void
    {
        $blockedUser1 = $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR)
        );
        $blockedUser2 = $this->createBlockedUser(
            Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR)
        );

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $userJohn = $userRepository->findById(Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR));
        $userSarah = $userRepository->findById(Uuid::fromString(UserFixtures::USER_ID_SARAH_CONNOR));

        $this->sendWebhookCommand(UserFixtures::ADMIN_CHAT_ID, 'blocked_users');
        $this->assertResponseIsSuccessful();
        $message = $this->getFirstMessage(UserFixtures::ADMIN_CHAT_ID);

        self::assertEquals(
            'Список заблокированных участников. Вы можете добавить или убрать участника',
            $message['text']
        );
        self::assertEquals(
            [
                [
                    [
                        'text'          => sprintf(
                            '%s %s (@%s) - Убрать',
                            $userSarah->getFirstName(),
                            $userSarah->getLastName(),
                            $userSarah->getUsername(),
                        ),
                        'callback_data' => sprintf('remove_block:%s', $blockedUser2->getId()->toString()),
                    ]
                ],
                [
                    [
                        'text'          => sprintf(
                            '%s %s (@%s) - Убрать',
                            $userJohn->getFirstName(),
                            $userJohn->getLastName(),
                            $userJohn->getUsername(),
                        ),
                        'callback_data' => sprintf('remove_block:%s', $blockedUser1->getId()->toString()),
                    ],
                ],
                [
                    [
                        'text'          => 'Заблокировать участника',
                        'callback_data' => 'block_user',
                    ]
                ],
            ],
            $message['replyMarkup']
        );
    }
}
