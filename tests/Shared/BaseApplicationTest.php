<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Tests\Mock\MockTelegram;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseApplicationTest extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient([], [
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    /**
     * @return array<mixed>
     */
    protected function buildCommandObject(int $chatId, string $commandName): array
    {
        if ($chatId === 666666) {
            $firstName = 'Kyle';
            $lastName = 'Reese';
            $username = 'reese_admin';
        } else {
            $firstName = 'John';
            $lastName = 'Connor';
            $username = 'connor_user';
        }

        return [
            'update_id' => 476767316,
            'message' => [
                'message_id' => 111,
                'from' => [
                    'id' => $chatId,
                    'is_bot' => false,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => $chatId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                    'type' => 'private',
                ],
                'date' => 1680272755,
                'text' => sprintf('/%s', $commandName),
                'entities' => [
                    [
                        'offset' => 0,
                        'length' => 6,
                        'type' => 'bot_command',
                    ],
                ],
            ],
        ];
    }

    protected function sendWebhookRequest(int $chatId, string $commandName): void
    {
        MockTelegram::$messages = [];
        $this->client->request(
            method: 'POST',
            uri: '/webhook',
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode($this->buildCommandObject($chatId, $commandName)),
        );
    }

    /**
     * @return array<mixed>
     */
    public function getFirstMessage(int $chatId): array
    {
        return MockTelegram::$messages[$chatId][0];
    }
}
