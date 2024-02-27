<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\GenericText;

use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommandHandler;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\WithConsecutive;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\UserBan\Domain\UserBanRepository;
use App\UserWarning\Domain\UserWarningRepository;
use App\WaitList\Domain\WaitingUserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminGenericTextCommandHandlerTest extends TestCase
{
    public function testReceivingMessageForEveryone(): void
    {
        $adminChatId = 123;
        $text = 'Message for all users';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_MESSAGE_FOR_EVERYONE);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

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
        $userRepository
            ->method('findAllExceptUsernames')
            ->with([])
            ->willReturn([$user1, $user2]);


        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::exactly(3))
            ->method('sendMessage')
            ->with(
                ...
                WithConsecutive::create(
                    [$user1ChatId, $text, []],
                    [$user2ChatId, $text, []],
                    [
                        $adminChatId,
                        '✅ Сообщение успешно отправлено всем пользователям',
                        [
                            [
                                [
                                    'text'          => 'Перейти к списку ближайших клубов',
                                    'callback_data' => 'back_to_admin_list',
                                ],
                            ]
                        ]
                    ],
                )
            );

        $handler = $this->getHandler(userRepository: $userRepository, telegram: $telegram);
        $handler->__invoke($command);
    }

    private function getHandler(
        MockObject $userRepository = null,
        MockObject $speakingClubRepository = null,
        MockObject $telegram = null,
        MockObject $uuidProvider = null,
        MockObject $participationRepository = null,
        MockObject $clock = null,
        MockObject $eventDispatcher = null,
        MockObject $userRolesProvider = null,
        MockObject $waitingUserRepository = null,
        MockObject $userBanRepository = null,
        MockObject $userWarningRepository = null,
    ): AdminGenericTextCommandHandler {
        $userRepository = $userRepository ?? $this->createMock(UserRepository::class);
        $speakingClubRepository = $speakingClubRepository ?? $this->createMock(SpeakingClubRepository::class);
        $telegram = $telegram ?? $this->createMock(TelegramInterface::class);
        $uuidProvider = $uuidProvider ?? $this->createMock(UuidProvider::class);
        $participationRepository = $participationRepository ?? $this->createMock(ParticipationRepository::class);
        $clock = $clock ?? $this->createMock(Clock::class);
        $eventDispatcherInterface = $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class);
        $userRolesProvider = $userRolesProvider ?? $this->createMock(UserRolesProvider::class);
        $waitingUserRepository = $waitingUserRepository ?? $this->createMock(WaitingUserRepository::class);
        $userBanRepository = $userBanRepository ?? $this->createMock(UserBanRepository::class);
        $userWarningRepository = $userWarningRepository ?? $this->createMock(UserWarningRepository::class);
        $loggerInterface = $this->createMock(LoggerInterface::class);

        return new AdminGenericTextCommandHandler(
            $userRepository,
            $speakingClubRepository,
            $telegram,
            $uuidProvider,
            $participationRepository,
            $clock,
            $eventDispatcherInterface,
            $userRolesProvider,
            $waitingUserRepository,
            $userBanRepository,
            $userWarningRepository,
            $loggerInterface
        );
    }
}
