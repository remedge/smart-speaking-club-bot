<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminAddParticipant;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminAddParticipantCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private UserRepository $userRepository,
        private SpeakingClubRepository $speakingClubRepository,
    ) {
    }

    public function __invoke(AdminAddParticipantCommand $command): void
    {
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);
        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Разговорный клуб не найден',
                replyMarkup: [
                    [[
                        'text' => 'Вернуться к списку',
                        'callback_data' => 'back_to_admin_list',
                    ]],
                ]
            );
            return;
        }

        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Вы не зарегистрированы в системе',
            );
            return;
        }

        $user->setState(UserStateEnum::RECEIVING_PARTICIPANT);
        $user->setActualSpeakingClubData([
            'participantSpeakingClubId' => $command->speakingClubId->toString(),
        ]);
        $this->userRepository->save($user);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Введите username участника, которого хотите добавить в разговорный клуб',
        );
    }
}
