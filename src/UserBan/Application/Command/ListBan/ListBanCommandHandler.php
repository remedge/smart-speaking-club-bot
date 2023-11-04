<?php

declare(strict_types=1);

namespace App\UserBan\Application\Command\ListBan;

use App\Shared\Domain\TelegramInterface;
use App\UserBan\Application\Query\UserBanQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBanCommandHandler
{
    public function __construct(
        private UserBanQuery $userBanQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(ListBanCommand $command): void
    {
        $userBans = $this->userBanQuery->findAllBan();

        $buttons = [];
        foreach ($userBans as $userBan) {
            $buttons[] = [
                [
                    'text' => sprintf(
                        '%s %s (@%s) - Убрать',
                        $userBan->firstName,
                        $userBan->lastName,
                        $userBan->username,
                    ),
                    'callback_data' => sprintf('remove_ban:%s', $userBan->id->toString()),
                ]
            ];
        }
        $buttons[] = [[
            'text' => 'Забанить участника',
            'callback_data' => 'add_ban',
        ]];

        if (count($userBans) === 0) {
            $text = 'Никто не забанен';
        } else {
            $text = 'Список забаненных участников. Вы можете добавить или убрать участника';
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
