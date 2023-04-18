<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Telegram;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Command\Admin\AdminListUpcomingSpeakingClubs\AdminListUpcomingSpeakingClubsCommand;
use App\User\Application\Command\Admin\InitClubCreation\InitClubCreationCommand;
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

        Request::editMessageText($data);
    }

    public function setCommandsMenu(): void
    {
        Request::setMyCommands([
            'scope' => new BotCommandScopeDefault(),
            'commands' => [
                new BotCommand([
                    'command' => InitClubCreationCommand::COMMAND_NAME,
                    'description' => InitClubCreationCommand::COMMAND_DESCRIPTION,
                ]),
                new BotCommand([
                    'command' => AdminListUpcomingSpeakingClubsCommand::COMMAND_NAME,
                    'description' => AdminListUpcomingSpeakingClubsCommand::COMMAND_DESCRIPTION,
                ]),
            ],
        ]);
    }
}
