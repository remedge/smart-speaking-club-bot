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

    public function getInput(Request $request): string
    {
        return $request->getContent();
    }

    public function setCommandsMenu(): void
    {
    }

    public function editMessageText(int $chatId, int $messageId, string $text, array $replyMarkup = []): void
    {
    }
}
