<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Shared\Domain\TelegramInterface;
use Symfony\Component\HttpFoundation\Request;

class MockTelegram implements TelegramInterface
{
    /**
     * @var array<mixed>
     */
    public static $messages = [];

    /**
     * @var array<mixed>
     */
    private array $request;

    public function setWebhook(): string
    {
        return 'ok';
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        self::$messages[$chatId][] = [
            'text' => $text,
            'replyMarkup' => $replyMarkup,
        ];
    }

    public function setCommandsMenu(): void
    {
    }

    public function editMessageText(int $chatId, int $messageId, string $text, array $replyMarkup = []): void
    {
        self::$messages[$chatId][$messageId] = [
            'text' => $text,
            'replyMarkup' => $replyMarkup,
        ];
    }

    public function parseUpdateFromRequest(Request $request): void
    {
        $this->request = json_decode($request->getContent(), true);
    }

    public function isCallbackQuery(): bool
    {
        if (isset($this->request['callback_query'])) {
            return true;
        }
        return false;
    }

    public function getChatId(): int
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['message']['chat']['id'];
        } else {
            return $this->request['message']['chat']['id'];
        }
    }

    public function getMessageId(): int
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['message']['message_id'];
        } else {
            return $this->request['message']['message_id'];
        }
    }

    public function getText(): string
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['data'];
        } else {
            return $this->request['message']['text'];
        }
    }

    public function getFirstName(): string
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['message']['chat']['first_name'];
        } else {
            return $this->request['message']['chat']['first_name'];
        }
    }

    public function getLastName(): string
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['message']['chat']['last_name'];
        } else {
            return $this->request['message']['chat']['last_name'];
        }
    }

    public function getUsername(): string
    {
        if (isset($this->request['callback_query'])) {
            return $this->request['callback_query']['message']['chat']['username'];
        } else {
            return $this->request['message']['chat']['username'];
        }
    }
}
