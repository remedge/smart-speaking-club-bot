<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Start;

use App\Shared\Domain\TelegramInterface;
use App\User\Application\Query\UserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class StartCommandHandler
{
    public function __construct(
        private readonly TelegramInterface $telegram,
        private readonly UserQuery $userQuery,
    ) {
    }

    public function __invoke(StartCommand $command): void
    {
        $usersCount = $this->userQuery->getTotalUsersCount();

        $text = 'Привет! Я – умный бот-помощник. Расскажу о всех предстоящих разговорных клубах в нашей школе, и помогу записаться на любой из них за два клика. А еще напомню о ваших предстоящих встречах. 

Нажмите “📅 Посмотреть расписание“, чтобы увидеть список всех предстоящих клубов и выберите тот, на который хотите записаться 😊';
        if ($command->isAdmin === true) {
            $text .= "\n\n👮‍️ Вы администратор бота, поэтому у вас есть доступ к дополнительным функциям\n\n👩‍🏫 Всего в базе бота человек: " . $usersCount;
        }

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: $text,
            replyMarkup: ($command->isAdmin === true)
                ? [
                    [[
                        'text' => '📅 Посмотреть расписание',
                        'callback_data' => 'admin_upcoming_clubs',
                    ]],
                    [[
                        'text' => '📝 Добавить новый разговорный клуб',
                        'callback_data' => 'admin_create_club',
                    ]],
                    [[
                        'text' => '📤 Отправить сообщение всем пользователям бота',
                        'callback_data' => 'admin_send_message',
                    ]],
                ]
                : [
                    [[
                        'text' => '📅 Посмотреть расписание',
                        'callback_data' => 'upcoming_clubs',
                    ]],
                    [[
                        'text' => '💌 Посмотреть мои записи',
                        'callback_data' => 'my_upcoming_clubs',
                    ]],
                ],
        );
    }
}
