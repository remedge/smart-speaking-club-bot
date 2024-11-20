<?php

namespace App\Tests\Unit\User\Application\Command\Admin\Notifications;

use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\Tests\Shared\BaseApplicationTest;
use App\Tests\WithConsecutive;
use App\User\Application\Command\Admin\Notifications\SendMessageToAllUsersCommand;
use App\User\Application\Command\Admin\Notifications\SendMessageToAllUsersCommandHandler;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Psr\Log\LoggerInterface;

class SendMessageToAllUsersCommandHandlerTest extends BaseApplicationTest
{
    public function testInvoke()
    {
        $text = 'some text';

        $user1ChatId = 111;
        $user1 = $this->createMock(User::class);
        $user1
            ->method('getChatId')
            ->willReturn(
                $user1ChatId
            );
        $user2ChatId = 222;
        $user2 = $this->createMock(User::class);
        $user2
            ->method('getChatId')
            ->willReturn($user2ChatId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findAllExceptUsernames')
            ->with([])
            ->willReturn([$user1, $user2]);

        $recipients = [$user1, $user2];

        $adminChatId = 333;

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::exactly(2))
            ->method('sendMessage')
            ->with(
                ...
                WithConsecutive::create(
                    [$user1ChatId, $text, []],
                    [$user2ChatId, $text, []]
                )
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Message sent to all users', [
                    'adminChatId' => $adminChatId,
                    'text'        => $text,
                ]
            );

        $command = new SendMessageToAllUsersCommand($text, $adminChatId);

        $userRolesProvider = $this->createMock(UserRolesProvider::class);
        $userRolesProvider
            ->method('getAdminUsernames')
            ->willReturn([]);

        $handler = new SendMessageToAllUsersCommandHandler($userRolesProvider, $userRepository, $telegram, $logger);
        $handler->__invoke($command);
    }
}
