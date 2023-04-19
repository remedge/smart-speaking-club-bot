<?php

declare(strict_types=1);

namespace App\User\Application\Command\Admin\InitClubEdition;

use App\Shared\Domain\TelegramInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class InitClubEditionCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(InitClubEditionCommand $command): void
    {
        $user = $this->userRepository->getByChatId($command->chatId);

        $user->setState(UserStateEnum::RECEIVING_NAME_FOR_EDITING);
        $user->setActualSpeakingClubData([
            'id' => $command->speakingClubId->toString(),
        ]);
        $this->userRepository->save($user);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Введите новое название клуба',
        );
    }
}
