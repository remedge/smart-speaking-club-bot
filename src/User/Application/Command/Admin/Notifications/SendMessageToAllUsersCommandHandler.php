<?php

namespace App\User\Application\Command\Admin\Notifications;

use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\User\Domain\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendMessageToAllUsersCommandHandler
{
    public function __construct(
        private readonly UserRolesProvider $userRolesProvider,
        private readonly UserRepository $userRepository,
        private readonly TelegramInterface $telegram,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(SendMessageToAllUsersCommand $command): void
    {
        $recipients = $this->userRepository->findAllExceptUsernames($this->userRolesProvider->getAdminUsernames());

        foreach ($recipients as $recipient) {
            $this->telegram->sendMessage(
                chatId: $recipient->getChatId(),
                text: $command->text,
            );
        }

        $this->logger->info('Message sent to all users', [
            'adminChatId' => $command->adminChatId,
            'text'        => $command->text,
        ]);
    }
}
