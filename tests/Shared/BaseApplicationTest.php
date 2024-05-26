<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\BlockedUser\Domain\BlockedUserRepository;
use App\Shared\Application\Clock;
use App\Shared\Application\Command\GenericText\AdminGenericTextCommandHandler;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\Tests\Mock\MockTelegram;
use App\Tests\Mock\MockUuidProvider;
use App\Tests\TestCaseTrait;
use App\User\Domain\UserRepository;
use App\UserBan\Domain\UserBanRepository;
use App\UserWarning\Domain\UserWarningRepository;
use App\WaitList\Domain\WaitingUserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class BaseApplicationTest extends WebTestCase
{
    use TestCaseTrait;

    public const KYLE_REESE_CHAT_ID = 111111;
    const MESSAGE_ID = 123;

    protected KernelBrowser $client;
    protected UuidProvider $uuidProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient([], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->uuidProvider = new MockUuidProvider();
        $clock = $this->getContainer()->get(Clock::class);
        $clock->setNow(date('Y-m-d H:i:s'));
    }

    protected function sendWebhookCommand(int $chatId, string $commandName): void
    {
        MockTelegram::$messages = [];

        if ($chatId === self::KYLE_REESE_CHAT_ID) {
            $firstName = 'Kyle';
            $lastName = 'Reese';
            $username = 'kyle_reese';
        } else {
            $firstName = 'John';
            $lastName = 'Connor';
            $username = 'john_connor';
        }

        $body = [
            'update_id' => 476767316,
            'message'   => [
                'message_id' => 111,
                'from'       => [
                    'id'            => $chatId,
                    'is_bot'        => false,
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'username'      => $username,
                    'language_code' => 'ru',
                ],
                'chat'       => [
                    'id'         => $chatId,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'username'   => $username,
                    'type'       => 'private',
                ],
                'date'       => 1680272755,
                'text'       => sprintf('/%s', $commandName),
                'entities'   => [
                    [
                        'offset' => 0,
                        'length' => 6,
                        'type'   => 'bot_command',
                    ],
                ],
            ],
        ];

        $this->client->request(
            method: 'POST',
            uri: '/webhook',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode($body),
        );
    }

    protected function sendWebhookCallbackQuery(int $chatId, int $messageId, string $callbackData): void
    {
        MockTelegram::$messages = [];

        if ($chatId === 666666) {
            $firstName = 'Kyle';
            $lastName = 'Reese';
            $username = 'kyle_reese';
        } else {
            $firstName = 'John';
            $lastName = 'Connor';
            $username = 'john_connor';
        }

        $body = [
            'update_id'      => 156705969,
            'callback_query' => [
                'id'            => '4210226841674178',
                'from'          => [
                    'id'            => $chatId,
                    'is_bot'        => false,
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'username'      => $username,
                    'language_code' => 'ru',
                    'is_premium'    => true,
                ],
                'message'       => [
                    'message_id'   => $messageId,
                    'from'         => [
                        'id'         => 5951631065,
                        'is_bot'     => true,
                        'first_name' => 'bot_first_name',
                        'username'   => $username,
                    ],
                    'chat'         => [
                        'id'         => $chatId,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'username'   => $username,
                        'type'       => 'private',
                    ],
                    'date'         => 1682265056,
                    'edit_date'    => 1682265062,
                    'text'         => 'initial_text',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text'          => 'initial_text',
                                    'callback_data' => $callbackData,
                                ],
                            ],
                        ],
                    ],
                ],
                'chat_instance' => '1357108034902232118',
                'data'          => $callbackData,
            ],
        ];

        $this->client->request(
            method: 'POST',
            uri: '/webhook',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode($body),
        );
    }

    /**
     * @return array<mixed>
     */
    public function getMessages(): array
    {
        return MockTelegram::$messages;
    }

    /**
     * @return array<mixed>
     */
    public function getFirstMessage(int $chatId): array
    {
        return MockTelegram::$messages[$chatId][0];
    }

    /**
     * @return array<mixed>
     */
    public function getMessagesByChatId(int $chatId): array
    {
        return MockTelegram::$messages[$chatId];
    }

    /**
     * @return array<mixed>
     */
    public function getMessage(int $chatId, int $messageId): array
    {
        return MockTelegram::$messages[$chatId][$messageId];
    }

    protected function getAdminGenericTextCommandHandler(
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
        MockObject $blockedUserRepository = null,
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
        $blockedUserRepository = $blockedUserRepository ?? $this->createMock(BlockedUserRepository::class);
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
            $blockedUserRepository,
            $logger
        );
    }
}
