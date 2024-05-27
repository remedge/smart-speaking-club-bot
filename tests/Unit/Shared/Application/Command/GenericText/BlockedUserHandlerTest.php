<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\GenericText;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Ramsey\Uuid\UuidInterface;

class BlockedUserHandlerTest extends BaseApplicationTest
{
    public function testReceivingUsernameToBlock(): void
    {
        $adminChatId = 123;
        $text = 'someUserName';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_USERNAME_TO_BLOCK);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with([]);
        $adminUser
            ->method('getChatId')
            ->willReturn($adminChatId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $userToBlockUuid = $this->createMock(UuidInterface::class);
        $blockedUserUuid = $this->createMock(UuidInterface::class);

        $uuidProvider = $this->createMock(UuidProvider::class);
        $uuidProvider
            ->method('provide')
            ->willReturn($blockedUserUuid);

        $userToBlock = $this->createMock(User::class);
        $userToBlock
            ->method('getId')
            ->willReturn($userToBlockUuid);

        $userRepository
            ->method('findByUsername')
            ->with($text)
            ->willReturn($userToBlock);

        $clock = $this->getContainer()->get(Clock::class);

        $blockedUser = new BlockedUser(
            id: $userToBlockUuid,
            userId: $userToBlockUuid,
            createdAt: $clock->now(),
        );
        $blockedUserRepository = $this->createMock(BlockedUserRepository::class);
        $blockedUserRepository
            ->method('save')
            ->with($blockedUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Пользователь успешно заблокирован'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
            uuidProvider: $uuidProvider,
            blockedUserRepository: $blockedUserRepository
        );
        $handler->__invoke($command);
    }

    public function testReceivingUsernameToBlockWhenNoUser(): void
    {
        $adminChatId = 123;
        $text = 'someUserName';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_USERNAME_TO_BLOCK);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with([]);
        $adminUser
            ->method('getChatId')
            ->willReturn($adminChatId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $userRepository
            ->method('findByUsername')
            ->with($text)
            ->willReturn(null);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Такого пользователя нет в базе бота'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram
        );
        $handler->__invoke($command);
    }

    public function testReceivingUsernameToBlockWhenItIsAdmin(): void
    {
        $adminChatId = 123;
        $text = 'someUserName';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_USERNAME_TO_BLOCK);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with([]);
        $adminUser
            ->method('getChatId')
            ->willReturn($adminChatId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $userToBlock = $this->createMock(User::class);
        $userToBlock
            ->method('getUsername')
            ->willReturn($text);

        $userRepository
            ->method('findByUsername')
            ->with($text)
            ->willReturn($userToBlock);

        $userRolesProvider = $this->createMock(UserRolesProvider::class);
        $userRolesProvider
            ->method('isUserAdmin')
            ->with($text)
            ->willReturn(true);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Нельзя заблокировать администратора'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
            userRolesProvider: $userRolesProvider
        );
        $handler->__invoke($command);
    }
}
