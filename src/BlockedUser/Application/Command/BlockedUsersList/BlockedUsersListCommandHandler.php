<?php

declare(strict_types=1);

namespace App\BlockedUser\Application\Command\BlockedUsersList;

use App\BlockedUser\Infrastructure\Doctrine\Repository\DoctrineBlockedUserRepository;
use App\Shared\Domain\TelegramInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BlockedUsersListCommandHandler
{
    public function __construct(
        private DoctrineBlockedUserRepository $repository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(BlockedUsersListCommand $command): void
    {
        $blockedUsers = $this->repository->findAll();

        $buttons = [];
        foreach ($blockedUsers as $blockedUser) {
            $buttons[] = [
                [
                    'text'          => sprintf(
                        '%s %s (@%s) - Убрать',
                        $blockedUser['firstName'],
                        $blockedUser['lastName'],
                        $blockedUser['username'],
                    ),
                    'callback_data' => sprintf('remove_block:%s', $blockedUser['user_id']->toString()),
                ]
            ];
        }
        $buttons[] = [
            [
                'text'          => 'Заблокировать участника',
                'callback_data' => 'block_user',
            ]
        ];

        if (count($blockedUsers) === 0) {
            $text = 'Список заблокированных пользователей пуст';
        } else {
            $text = 'Список заблокированных участников. Вы можете добавить или убрать участника';
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
