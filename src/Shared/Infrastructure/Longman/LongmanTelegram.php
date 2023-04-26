<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Longman;

use App\Shared\Application\Clock;
use App\Shared\Application\Command\Help\HelpCommand;
use App\Shared\Application\Command\Start\StartCommand;
use App\Shared\Domain\TelegramInterface;
use Longman\TelegramBot\Entities\BotCommand;
use Longman\TelegramBot\Entities\BotCommandScope\BotCommandScopeDefault;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request as TelegramRequest;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class LongmanTelegram implements TelegramInterface
{
    private Telegram $telegram;

    private ?Update $update;

    public function __construct(
        string $apiKey,
        private readonly string $botUsername,
        private readonly string $webhookUrl,
        private bool $loggingInput,
        private Clock $clock,
    ) {
        $this->telegram = new Telegram(
            api_key: $apiKey,
            bot_username: $botUsername
        );
        $this->update = null;
    }

    public function parseUpdateFromRequest(Request $request): void
    {
        $input = TelegramRequest::getInput();

        $this->update = new Update(json_decode($input, true), $this->botUsername);

        if ($this->loggingInput === true) {
            $filesystem = new Filesystem();
            $dir = __DIR__ . '/../../../../var/requests';
            $filesystem->mkdir(Path::normalize($dir));

            $finder = new Finder();
            $finder->files()->in($dir);

            if ($finder->count() >= 50) {
                $finder->sortByChangedTime();
                for ($i = 0; $i <= $finder->count() - 50; $i++) {
                    $finder->getIterator()->current()->isFile();
                    $filesystem->remove($finder->getIterator()->current()->getPathname());
                    $finder->getIterator()->next();
                }
            }

            $data = json_encode(json_decode($input), JSON_PRETTY_PRINT);
            $filesystem->dumpFile(
                filename: Path::normalize(
                    sprintf(
                        '%s/%s_%s.json',
                        $dir,
                        ($this->clock->now())->format('Y-m-d_H:i:s'),
                        $this->getUsername()
                    )
                ),
                content: $data
            );
        }
    }

    public function isEditedMessage(): bool
    {
        if ($this->update === null) {
            $this->update = new Update(json_decode(TelegramRequest::getInput(), true), $this->botUsername);
        }

        return property_exists($this->update, 'edited_message');
    }

    public function isCallbackQuery(): bool
    {
        if ($this->update === null) {
            $this->update = new Update(json_decode(TelegramRequest::getInput(), true), $this->botUsername);
        }

        return property_exists($this->update, 'callback_query');
    }

    public function getChatId(): int
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getMessage()->getChat()->getId();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getChat()->getId();
        } else {
            return $this->update->getMessage()->getChat()->getId();
        }
    }

    public function getText(): string
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getData();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getText();
        } else {
            return $this->update->getMessage()->getText();
        }
    }

    public function getFirstName(): ?string
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getFrom()->getFirstName();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getFrom()->getFirstName();
        } else {
            return $this->update->getMessage()->getFrom()->getFirstName();
        }
    }

    public function getLastName(): ?string
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getFrom()->getLastName();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getFrom()->getLastName();
        } else {
            return $this->update->getMessage()->getFrom()->getLastName();
        }
    }

    public function getUsername(): string
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getFrom()->getUsername();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getFrom()->getUsername();
        } else {
            return $this->update->getMessage()->getFrom()->getUsername();
        }
    }

    public function getMessageId(): int
    {
        if ($this->isCallbackQuery() === true) {
            return $this->update->getCallbackQuery()->getMessage()->getMessageId();
        } elseif ($this->isEditedMessage() === true) {
            return $this->update->getEditedMessage()->getMessageId();
        } else {
            return $this->update->getMessage()->getMessageId();
        }
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
            /**
             * @psalm-suppress TooManyArguments
             */
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
