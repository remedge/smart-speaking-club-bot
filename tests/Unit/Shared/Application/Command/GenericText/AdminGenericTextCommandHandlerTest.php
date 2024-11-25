<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\GenericText;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Application\Command\Admin\Notifications\SendMessageToAllUsersCommand;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

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

        $envelope = new Envelope(new stdClass());
        $sendMessageCommand = new SendMessageToAllUsersCommand($text, $adminChatId);
        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($sendMessageCommand)
            ->willReturn($envelope);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                '✅ Сообщение отправлено в очередь рассылки всем пользователям',
                [
                    [
                        [
                            'text'          => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]
                ]
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
            commandBus: $commandBus
        );
        $handler->__invoke($command);
    }

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
                'old_data'        => $oldClubData,
                'new_data'        => $newClubData,
            ]);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with($adminChatId, 'Клуб успешно изменен');

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            speakingClubRepository: $speakingClubRepository,
            telegram: $telegram,
            logger: $logger
        );
        $handler->__invoke($command);
    }
}
