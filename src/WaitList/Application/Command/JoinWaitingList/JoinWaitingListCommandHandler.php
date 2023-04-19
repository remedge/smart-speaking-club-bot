<?php

declare(strict_types=1);

namespace App\WaitList\Application\Command\JoinWaitingList;

use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\User\Application\Query\UserQuery;
use App\WaitList\Domain\WaitingUser;
use App\WaitList\Domain\WaitingUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class JoinWaitingListCommandHandler
{
    public function __construct(
        private WaitingUserRepository $waitingUserRepository,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
    ) {
    }

    public function __invoke(JoinWaitingListCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $waitingUser = $this->waitingUserRepository->findByUserIdAndSpeakingClubId(
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
        );

        if ($waitingUser) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Вы уже находитесь в списке ожидания',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]],
            );
            return;
        }

        $this->waitingUserRepository->save(new WaitingUser(
            id: $this->uuidProvider->provide(),
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
        ));

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Вы успешно добавлены в список ожидания, я сообщу вам, когда появится свободное место',
            replyMarkup: [[
                [
                    'text' => 'Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ],
            ]],
        );
    }
}
