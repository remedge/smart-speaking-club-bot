<?php

declare(strict_types=1);

namespace App\UserWarning\Application\Command\ListWarning;

use App\Shared\Domain\TelegramInterface;
use App\UserWarning\Application\Query\UserWarningQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListWarningCommandHandler
{
    public function __construct(
        private UserWarningQuery $userWarningQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(ListWarningCommand $command): void
    {
        $userWarnings = $this->userWarningQuery->findAllWarning();

        $buttons = [];
        foreach ($userWarnings as $userWarning) {
            $buttons[] = [
                [
                    'text' => sprintf(
                        '%s %s (@%s) - Убрать',
                        $userWarning->firstName,
                        $userWarning->lastName,
                        $userWarning->username,
                    ),
                    'callback_data' => sprintf('remove_warning:%s', $userWarning->id->toString()),
                ]
            ];
        }
        $buttons[] = [[
            'text' => 'Добавить участника в список предупреждения',
            'callback_data' => 'add_warning',
        ]];

        if (count($userWarnings) === 0) {
            $text = 'Список предупреждений пуст';
        } else {
            $text = 'Список участников с предупреждениями. Вы можете добавить или убрать участника';
        }

        if ($command->messageId !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: $text,
                replyMarkup: $buttons
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: $text,
                replyMarkup: $buttons
            );
        }
    }
}
