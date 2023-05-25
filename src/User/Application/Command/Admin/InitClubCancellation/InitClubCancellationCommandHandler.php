<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubCancellation;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitClubCancellationCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(InitClubCancellationCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_CONFIRMATION_CLUB_CANCELLATION);
        $user->setActualSpeakingClubData([
            'id' => $command->speakingClubId,
        ]);
        $this->userRepository->save($user);

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'Чтобы удалить клуб, введите полностью его название',
        );
    }
}
