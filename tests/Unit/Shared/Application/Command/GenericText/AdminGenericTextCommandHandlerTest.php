<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\GenericText;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommand;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Event\SpeakingClubScheduleChangedEvent;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    public function testReceivingDescriptionForCreation(): void
    {
        $adminChatId = 123;
        $text = 'some description';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $actualClubData = [
            'id'   => '00000000-0000-0000-0000-000000000001',
            'name' => 'name',
        ];
        $newClubData = [
            'id'          => '00000000-0000-0000-0000-000000000001',
            'name'        => 'name',
            'description' => $text,
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_CREATION);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($actualClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите username преподавателя(без @) или "пропустить" чтобы пропустить'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingTeacherUsernameForCreation(): void
    {
        $adminChatId = 123;
        $text = 'teacherUsername';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $actualClubData = [
            'id'          => '00000000-0000-0000-0000-000000000001',
            'name'        => 'name',
            'description' => 'description',
        ];
        $newClubData = [
            'id'               => '00000000-0000-0000-0000-000000000001',
            'name'             => 'name',
            'description'      => 'description',
            'teacher_username' => $text,
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($actualClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите ссылку на разговорный клуб или "пропустить" чтобы пропустить'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider skipVersionsDataProvider
     * @param string $skipText
     * @return void
     */
    public function testReceivingTeacherUsernameForCreationWhenSkip(string $skipText): void
    {
        $adminChatId = 123;
        $text = $skipText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION);
        $adminUser
            ->expects(self::never())
            ->method('setActualSpeakingClubData');
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите ссылку на разговорный клуб или "пропустить" чтобы пропустить'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public static function skipVersionsDataProvider(): array
    {
        return [
            ['пропустить'],
            ['Пропустить'],
            ['ПРОПУСТИТЬ'],
            [' пропустить '],
            [' проПустИть '],
            ["пропустить\n"],
        ];
    }

    public static function eraseVersionsDataProvider(): array
    {
        return [
            ['стереть'],
            ['Стереть'],
            ['СТЕРЕТЬ'],
            [' стереть '],
            [' стЕреТь '],
            ["стереть\n"],
        ];
    }

    public function testReceivingLinkToClubForCreation(): void
    {
        $adminChatId = 123;
        $text = 'some url';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $actualClubData = [
            'id'               => '00000000-0000-0000-0000-000000000001',
            'name'             => 'name',
            'description'      => 'description',
            'teacher_username' => 'teacher_username',
        ];
        $newClubData = [
            'id'               => '00000000-0000-0000-0000-000000000001',
            'name'             => 'name',
            'description'      => 'description',
            'teacher_username' => 'teacher_username',
            'link'             => $text,
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_CREATION);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($actualClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите минимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider skipVersionsDataProvider
     * @param string $skipText
     * @return void
     */
    public function testReceivingLinkToClubForCreationWhenSkip(string $skipText): void
    {
        $adminChatId = 123;
        $text = $skipText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_CREATION);
        $adminUser
            ->expects(self::never())
            ->method('setActualSpeakingClubData');
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите минимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingMinParticipantsCountForCreation(): void
    {
        $adminChatId = 123;
        $text = '4';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $actualClubData = [
            'id'               => '00000000-0000-0000-0000-000000000001',
            'name'             => 'name',
            'description'      => 'description',
            'teacher_username' => 'teacher_username',
            'link'             => 'link',
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'name',
            'description'            => 'description',
            'teacher_username'       => 'teacher_username',
            'link'                   => 'link',
            'min_participants_count' => $text
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($actualClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);
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

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите максимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingDateForCreation(): void
    {
        $adminChatId = 123;
        $text = (new DateTimeImmutable())->modify('+5 hours')->format('d.m.Y H:i');
        $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $text);

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $data = [
            'name'                   => 'new name',
            'description'            => 'new description',
            'teacher_username'       => 'teacher_username',
            'link'                   => 'link',
            'min_participants_count' => 12,
            'max_participants_count' => 12
        ];
        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_DATE_FOR_CREATION);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::IDLE);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($data);
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

        $speakingClubUuid = $this->createMock(UuidInterface::class);

        $uuidProvider = $this->createMock(UuidProvider::class);
        $uuidProvider
            ->method('provide')
            ->willReturn($speakingClubUuid);

        $speakingClub = new SpeakingClub(
            id: $speakingClubUuid,
            name: $data['name'],
            description: $data['description'],
            minParticipantsCount: $data['min_participants_count'],
            maxParticipantsCount: $data['max_participants_count'],
            date: $date,
            link: $data['link'],
            teacherUsername: $data['teacher_username'],
        );

        $speakingClubRepository = $this->createMock(SpeakingClubRepository::class);
        $speakingClubRepository
            ->expects(self::once())
            ->method('save')
            ->with($speakingClub);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with($adminChatId, 'Клуб успешно создан');

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            speakingClubRepository: $speakingClubRepository,
            telegram: $telegram,
            uuidProvider: $uuidProvider,
        );
        $handler->__invoke($command);
    }

    public function testReceivingDescriptionForEditing(): void
    {
        $adminChatId = 123;
        $text = 'some description';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => $text,
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_DESCRIPTION_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($oldClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новый username преподавателя(без @) ИЛИ "пропустить" чтобы оставить старый username ИЛИ "стереть", чтобы стереть'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingTeacherUsernameForEditing(): void
    {
        $adminChatId = 123;
        $text = 'some_teacher_username';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => $text,
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($oldClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новую ссылку на разговорный клуб ИЛИ "пропустить" чтобы оставить старый username ИЛИ "стереть", чтобы стереть'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider eraseVersionsDataProvider
     * @param string $eraseText
     * @return void
     */
    public function testReceivingTeacherUsernameForEditingWhenErase(string $eraseText): void
    {
        $adminChatId = 123;
        $text = $eraseText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => null,
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($oldClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новую ссылку на разговорный клуб ИЛИ "пропустить" чтобы оставить старый username ИЛИ "стереть", чтобы стереть'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider skipVersionsDataProvider
     * @param string $skipText
     * @return void
     */
    public function testReceivingTeacherUsernameForEditingWhenSkip(string $skipText): void
    {
        $adminChatId = 123;
        $text = $skipText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser->expects(self::never())->method('setActualSpeakingClubData');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новую ссылку на разговорный клуб ИЛИ "пропустить" чтобы оставить старый username ИЛИ "стереть", чтобы стереть'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingLinkToClubForEditing(): void
    {
        $adminChatId = 123;
        $text = 'some_url';

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => $text,
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_EDITING);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($oldClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новое минимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider eraseVersionsDataProvider
     * @param string $eraseText
     * @return void
     */
    public function testReceivingLinkToClubForEditingWhenErase(string $eraseText): void
    {
        $adminChatId = 123;
        $text = $eraseText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];
        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => null,
            'min_participants_count' => 11,
            'max_participants_count' => 11,
            'date'                   => date('d.m.2023 H:i'),
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_EDITING);
        $adminUser
            ->method('getActualSpeakingClubData')
            ->willReturn($oldClubData);
        $adminUser
            ->expects(self::once())
            ->method('setActualSpeakingClubData')
            ->with($newClubData);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новое минимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    /**
     * @dataProvider skipVersionsDataProvider
     * @param string $skipText
     * @return void
     */
    public function testReceivingLinkToClubForEditingWhenSkip(string $skipText): void
    {
        $adminChatId = 123;
        $text = $skipText;

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $adminUser = $this->createMock(User::class);
        $adminUser
            ->method('getState')
            ->willReturn(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
        $adminUser
            ->expects(self::once())
            ->method('setState')
            ->with(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_EDITING);
        $adminUser->expects(self::never())->method('setActualSpeakingClubData');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findByChatId')
            ->with($adminChatId)
            ->willReturn($adminUser);
        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with($adminUser);

        $telegram = $this->createMock(TelegramInterface::class);
        $telegram
            ->expects(self::once())
            ->method('sendMessage')
            ->with(
                $adminChatId,
                'Введите новое минимальное количество участников'
            );

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            telegram: $telegram,
        );
        $handler->__invoke($command);
    }

    public function testReceivingDateForEditing(): void
    {
        $adminChatId = 123;
        $oldDate = (new DateTimeImmutable())->modify('+2 hours')->format('d.m.Y H:i');
        $text = (new DateTimeImmutable())->modify('+5 hours')->format('d.m.Y H:i');

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'new name',
            'description'            => 'new description',
            'teacher_username'       => 'new_teacher_username',
            'link'                   => 'new_link',
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
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 12,
            'max_participants_count' => 12,
            'date'                   => $oldDate,
        ];
        $speakingClub = $this->createMock(SpeakingClub::class);
        $speakingClub->method('getId')->willReturn(Uuid::fromString($oldClubData['id']));
        $speakingClub->method('getName')->willReturn($oldClubData['name']);
        $speakingClub->method('getDescription')->willReturn($oldClubData['description']);
        $speakingClub->method('getTeacherUsername')->willReturn($oldClubData['teacher_username']);
        $speakingClub->method('getLink')->willReturn($oldClubData['link']);
        $speakingClub->method('getMinParticipantsCount')->willReturn($oldClubData['min_participants_count']);
        $speakingClub->method('getMaxParticipantsCount')->willReturn($oldClubData['max_participants_count']);
        $speakingClub
            ->method('getDate')
            ->willReturn(DateTimeImmutable::createFromFormat('d.m.Y H:i', $oldClubData['date']));

        $speakingClub->expects(self::once())->method('setName')->with($newClubData['name']);
        $speakingClub->expects(self::once())->method('setDescription')->with($newClubData['description']);
        $speakingClub->expects(self::once())->method('setTeacherUsername')->with($newClubData['teacher_username']);
        $speakingClub->expects(self::once())->method('setLink')->with($newClubData['link']);
        $speakingClub
            ->expects(self::once())
            ->method('setMinParticipantsCount')
            ->with($newClubData['min_participants_count']);
        $speakingClub
            ->expects(self::once())
            ->method('setMaxParticipantsCount')
            ->with($newClubData['max_participants_count']);
        $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $text);
        $speakingClub->expects(self::once())->method('setDate')->with($date);

        $speakingClubRepository = $this->createMock(SpeakingClubRepository::class);
        $speakingClubRepository
            ->method('findById')
            ->with('00000000-0000-0000-0000-000000000001')
            ->willReturn($speakingClub);

        $speakingClubRepository->expects(self::once())->method('save')->with($speakingClub);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new SpeakingClubScheduleChangedEvent($speakingClub->getId(), $text));

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
            eventDispatcher: $eventDispatcher,
            logger: $logger
        );
        $handler->__invoke($command);
    }

    public function testReceivingDateForEditingWhenSameDate(): void
    {
        $adminChatId = 123;
        $text = (new DateTimeImmutable())->modify('+5 hours')->format('d.m.Y H:i');

        $command = new AdminGenericTextCommand($adminChatId, $text);

        $newClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'new name',
            'description'            => 'new description',
            'teacher_username'       => 'new_teacher_username',
            'link'                   => 'new_link',
            'min_participants_count' => 12,
            'max_participants_count' => 12
        ];
        $adminUser = $this->createMock(User::class);
        $adminUser->method('getState')->willReturn(UserStateEnum::RECEIVING_DATE_FOR_EDITING);
        $adminUser->method('getActualSpeakingClubData')->willReturn($newClubData);
        $adminUser->method('getUsername')->willReturn('@admin_user_name');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findByChatId')->with($adminChatId)->willReturn($adminUser);

        $oldClubData = [
            'id'                     => '00000000-0000-0000-0000-000000000001',
            'name'                   => 'old name',
            'description'            => 'old description',
            'teacher_username'       => 'old_teacher_username',
            'link'                   => 'old_link',
            'min_participants_count' => 12,
            'max_participants_count' => 12,
            'date'                   => $text,
        ];
        $speakingClub = $this->createMock(SpeakingClub::class);
        $speakingClub->method('getId')->willReturn(Uuid::fromString($oldClubData['id']));
        $speakingClub->method('getName')->willReturn($oldClubData['name']);
        $speakingClub->method('getDescription')->willReturn($oldClubData['description']);
        $speakingClub->method('getTeacherUsername')->willReturn($oldClubData['teacher_username']);
        $speakingClub->method('getLink')->willReturn($oldClubData['link']);
        $speakingClub->method('getMinParticipantsCount')->willReturn($oldClubData['min_participants_count']);
        $speakingClub->method('getMaxParticipantsCount')->willReturn($oldClubData['max_participants_count']);
        $speakingClub
            ->method('getDate')
            ->willReturn(DateTimeImmutable::createFromFormat('d.m.Y H:i', $oldClubData['date']));

        $speakingClubRepository = $this->createMock(SpeakingClubRepository::class);
        $speakingClubRepository
            ->method('findById')
            ->with('00000000-0000-0000-0000-000000000001')
            ->willReturn($speakingClub);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::never())->method('dispatch');

        $logger = $this->createMock(LoggerInterface::class);
        $telegram = $this->createMock(TelegramInterface::class);

        $handler = $this->getAdminGenericTextCommandHandler(
            userRepository: $userRepository,
            speakingClubRepository: $speakingClubRepository,
            telegram: $telegram,
            eventDispatcher: $eventDispatcher,
            logger: $logger
        );
        $handler->__invoke($command);
    }
}
