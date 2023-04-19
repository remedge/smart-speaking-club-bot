<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminCancelSpeakingClub;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminCancelSpeakingClubCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AdminCancelSpeakingClubCommand $command): void
    {
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);
        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Такого клуба не существует',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]]
            );
            return;
        }

        if ($speakingClub->isCancelled() === true) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Клуб уже отменен',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]]
            );
            return;
        }

        $participants = $this->participationRepository->findBySpeakingClubId($speakingClub->getId());
        foreach ($participants as $participant) {
            $this->telegram->sendMessage(
                chatId: $participant['chatId'],
                text: sprintf(
                    'К сожалению, клуб "%s" %s отменен',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i')
                ),
            );
        }

        $speakingClub->cancel();
        $this->speakingClubRepository->save($speakingClub);

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: sprintf(
                'Клуб "%s" %s успешно отменен',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y H:i')
            ),
            replyMarkup: [[
                [
                    'text' => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_admin_list',
                ],
            ]]
        );
    }
}
