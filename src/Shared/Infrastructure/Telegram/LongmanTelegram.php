<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Telegram;

use App\Shared\Application\Clock;
use App\Shared\Application\Command\Help\HelpCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\Shared\Domain\TelegramInterface;
use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request as TelegramRequest;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;

class LongmanTelegram implements TelegramInterface
{
    private Telegram $telegram;

    public function __construct(
        string $apiKey,
        string $botUsername,
        private readonly string $webhookUrl,
        private bool $loggingInput,
        private Clock $clock,
    ) {
        $this->telegram = new Telegram(
            api_key: $apiKey,
            bot_username: $botUsername
        );
    }

    public function getInput(Request $request): string
    {
        $input = TelegramRequest::getInput();

        if ($this->loggingInput === true) {
            $filesystem = new Filesystem();
            $dir = __DIR__ . '/../../../../var/requests';
            $filesystem->mkdir(Path::normalize($dir));

            $data = json_encode(json_decode($input), JSON_PRETTY_PRINT);
            $filesystem->dumpFile(Path::normalize($dir . '/' . ($this->clock->now())->format('Y-m-d_H:i:s') . '.json'), $data);
        }

        return $input;
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

    /**
     * @param  array<int, array<int, array<string, string>>> $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, array $replyMarkup = []): void
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if (count($replyMarkup) > 0) {
            $data['reply_markup'] = new InlineKeyboard(...$replyMarkup);
        }

        TelegramRequest::sendMessage($data);
    }

    public function editMessageText(int $chatId, int $messageId, string $text, array $replyMarkup = []): void
    {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ];
        if (count($replyMarkup) > 0) {
            $data['reply_markup'] = new InlineKeyboard(...$replyMarkup);
        }

        TelegramRequest::editMessageText($data);
    }

    public function setCommandsMenu(): void
    {
        TelegramRequest::setMyCommands([
            'scope' => new BotCommandScopeDefault(),
            'commands' => [
                new BotCommand([
                    'command' => StartCommand::COMMAND_NAME,
                    'description' => StartCommand::COMMAND_DESCRIPTION,
                ]),
                new BotCommand([
                    'command' => HelpCommand::COMMAND_NAME,
                    'description' => HelpCommand::COMMAND_DESCRIPTION,
                ]),
            ],
        ]);
    }
}
