<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Telegram;

use App\Shared\Domain\TelegramInterface;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class LongmanTelegram implements TelegramInterface
{
    private Telegram $telegram;

    public function __construct(
        string $apiKey,
        string $botUsername,
        private readonly string $webhookUrl,
    ) {
        $this->telegram = new Telegram(
            api_key: $apiKey,
            bot_username: $botUsername
        );
    }

    public function getInput(): string
    {
        return Request::getInput();
    }

    public function setWebhook(): string
    {
        try {
            $result = $this->telegram->setWebhook($this->webhookUrl);
            if ($result->isOk()) {
                return $result->getDescription();
            } else {
                return 'Something went wrong';
            }
        } catch (TelegramException $e) {
            return $e->getMessage();
        }
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($replyMarkup !== null) {
            $data['reply_markup'] = new InlineKeyboard(...$replyMarkup);
        }

        Request::sendMessage($data);
    }
}
