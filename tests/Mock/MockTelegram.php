<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Shared\Domain\TelegramInterface;

class MockTelegram implements TelegramInterface
{
    public function setWebhook(): string
    {
        return 'ok';
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
    }

    public function getInput(): string
    {
        return '';
    }
}
