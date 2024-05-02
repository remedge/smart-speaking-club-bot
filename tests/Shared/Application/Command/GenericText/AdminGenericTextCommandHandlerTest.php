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
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\Tests\WithConsecutive;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\UserBan\Domain\UserBanRepository;
use App\UserWarning\Domain\UserWarningRepository;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminGenericTextCommandHandlerTest extends BaseApplicationTest
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

    public function testReceivingDateForEditing(): void
    {
        $adminChatId = 123;
        $text = (new DateTimeImmutable())->modify('+5 hours')->format('d.m.Y H:i');

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'new name',
            'description'            => 'new description',
            'min_participants_count' => 12,
            'max_participants_count' => 12
        ];
        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_DATE_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($newClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with([]);
        $adminUser
            ->method('getUsername')
            ->willReturn('@admin_user_name');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $speakingClub = $this->createMock(SpeakingClub::class);
        $speakingClub
            ->method('getId')
            ->willReturn(Uuid::fromString($oldClubData['id']));
        $speakingClub
            ->method('getName')
            ->willReturn($oldClubData['name']);
        $speakingClub
            ->method('getDescription')
            ->willReturn($oldClubData['description']);
        $speakingClub
            ->method('getMinParticipantsCount')
            ->willReturn($oldClubData['min_participants_count']);
        $speakingClub
            ->method('getMaxParticipantsCount')
            ->willReturn($oldClubData['max_participants_count']);
        $speakingClub
            ->method('getDate')
            ->willReturn(DateTimeImmutable::createFromFormat('d.m.Y H:i', $oldClubData['date']));

        $speakingClubRepository = $this->createMock(SpeakingClubRepository::class);
        $speakingClubRepository
            ->method('findById')
            ->with('00000000-0000-0000-0000-000000000001')
            ->willReturn($speakingClub);

        $newClubData['date'] = $text;
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with('The speaking club has been changed.', [
                'admin_user_name' => $adminUser->getUsername(),
                'admin_chat_id'   => $command->chatId,
                'old_data' => $oldClubData,
                'new_data' => $newClubData,
            ]);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with($adminChatId, 'Клуб успешно изменен');

        $handler = $this->getHandler(
            userRepository: $userRepository,
            speakingClubRepository: $speakingClubRepository,
            telegram: $telegram,
            logger: $logger
        );
        $handler->__invoke($command);
    }

    private function getHandler(
        MockObject $userRepository = null,
        MockObject $speakingClubRepository = null,
        MockObject $telegram = null,
        MockObject $uuidProvider = null,
        MockObject $participationRepository = null,
        MockObject $eventDispatcher = null,
        MockObject $userRolesProvider = null,
        MockObject $waitingUserRepository = null,
        MockObject $userBanRepository = null,
        MockObject $userWarningRepository = null,
        MockObject $logger = null,
    ): AdminGenericTextCommandHandler {
        $userRepository = $userRepository ?? $this->createMock(UserRepository::class);
        $speakingClubRepository = $speakingClubRepository ?? $this->createMock(SpeakingClubRepository::class);
        $telegram = $telegram ?? $this->createMock(TelegramInterface::class);
        $uuidProvider = $uuidProvider ?? $this->createMock(UuidProvider::class);
        $participationRepository = $participationRepository ?? $this->createMock(ParticipationRepository::class);
        $clock = $this->getContainer()->get(Clock::class);
        $eventDispatcherInterface = $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class);
        $userRolesProvider = $userRolesProvider ?? $this->createMock(UserRolesProvider::class);
        $waitingUserRepository = $waitingUserRepository ?? $this->createMock(WaitingUserRepository::class);
        $userBanRepository = $userBanRepository ?? $this->createMock(UserBanRepository::class);
        $userWarningRepository = $userWarningRepository ?? $this->createMock(UserWarningRepository::class);
        $logger = $logger ?? $this->createMock(LoggerInterface::class);

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
            $logger
        );
    }
}
