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

    protected function sendWebhookCommand(int $chatId, string $commandName): void
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
            'update_id' => 156705969,
            'callback_query' => [
                'id' => '4210226841674178',
                'from' => [
                    'id' => $chatId,
                    'is_bot' => false,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                    'language_code' => 'ru',
                    'is_premium' => true,
                ],
                'message' => [
                    'message_id' => $messageId,
                    'from' => [
                        'id' => 5951631065,
                        'is_bot' => true,
                        'first_name' => 'bot_first_name',
                        'username' => $username,
                    ],
                    'chat' => [
                        'id' => $chatId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                        'type' => 'private',
                    ],
                    'date' => 1682265056,
                    'edit_date' => 1682265062,
                    'text' => 'initial_text',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'initial_text',
                                    'callback_data' => $callbackData,
                                ],
                            ],
                        ],
                    ],
                ],
                'chat_instance' => '1357108034902232118',
                'data' => $callbackData,
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
    public function getFirstMessage(int $chatId): array
    {
        return MockTelegram::$messages[$chatId][0];
    }

    /**
     * @return array<mixed>
     */
    public function getMessage(int $chatId, int $messageId): array
    {
        return MockTelegram::$messages[$chatId][$messageId];
    }
}
