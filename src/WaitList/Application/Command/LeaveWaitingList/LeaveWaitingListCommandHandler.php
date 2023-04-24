<?php

declare(strict_types=1);

namespace App\WaitList\Application\Command\LeaveWaitingList;

use App\Shared\Domain\TelegramInterface;
use App\User\Application\Query\UserQuery;
use App\WaitList\Domain\WaitingUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LeaveWaitingListCommandHandler
{
    public function __construct(
        private WaitingUserRepository $waitingUserRepository,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(LeaveWaitingListCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $waitingUser = $this->waitingUserRepository->findOneByUserIdAndSpeakingClubId(
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
        );

        if ($waitingUser === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не находитесь в списке ожидания этого клуба',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]],
            );
            return;
        }

        $waitingUserEntity = $this->waitingUserRepository->findById($waitingUser['id']);

        if ($waitingUserEntity === null) {
            return;
        }

        $this->waitingUserRepository->remove($waitingUserEntity);

        if ($command->messageId !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '👌 Вы успешно вышли из списке ожидания',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]],
            );
        }
    }
}
