<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Telegram;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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

    public function getInput(): string
    {
        $input = Request::getInput();

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

        Request::sendMessage($data);
    }

    public function setCommandsMenu(bool $isAdmin): void
    {
        $userCommandsList = [
            new BotCommand([
                'command' => '/start',
                'description' => 'Начать работу с ботом',
            ]),
            new BotCommand([
                'command' => '/upcoming_clubs',
                'description' => 'Показать список ближайших разговорных клубов',
            ]),
            new BotCommand([
                'command' => '/user_upcoming_clubs',
                'description' => 'Показать список ближайших разговорных клубов, куда вы записаны',
            ]),
        ];
        $adminCommandsList = [];

        Request::setMyCommands([
            'scope' => new BotCommandScopeDefault(),
            'commands' => $userCommandsList,
        ]);
    }
}
