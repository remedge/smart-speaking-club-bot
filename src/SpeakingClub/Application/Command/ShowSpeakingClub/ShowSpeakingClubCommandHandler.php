<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\ShowSpeakingClub;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ShowSpeakingClubCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private SpeakingClubRepository $speakingClubRepository,
    ) {
    }

    public function __invoke(ShowSpeakingClubCommand $command): void
    {
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Такого клуба не существует',
            );
            return;
        }

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: sprintf(
                'Название: %s'
                . PHP_EOL . 'Описание: %s'
                . PHP_EOL . 'Дата: %s'
                . PHP_EOL . 'Максимальное количество участников: %s',
                $speakingClub->getName(),
                $speakingClub->getDescription(),
                $speakingClub->getDate()->format('d.m.Y H:i'),
                $speakingClub->getMaxParticipantsCount(),
            ),
            replyMarkup: [[
                [
                    'text' => 'Редактировать',
                    'callback_data' => sprintf('edit_speaking_club:%s', $speakingClub->getId()->toString()),
                ],
                [
                    'text' => 'Отменить',
                    'callback_data' => sprintf('cancel_speaking_club:%s', $speakingClub->getId()->toString()),
                ],
            ]]
        );
    }
}
