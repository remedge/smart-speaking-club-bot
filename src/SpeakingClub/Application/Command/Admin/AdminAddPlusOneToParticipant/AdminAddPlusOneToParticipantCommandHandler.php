<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminAddPlusOneToParticipant;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminAddPlusOneToParticipantCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AdminAddPlusOneToParticipantCommand $command): void
    {
        $participation = $this->participationRepository->findById($command->participationId);
        if ($participation === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Участник не найден',
                replyMarkup: [
                    [[
                        'text' => '<< Вернуться к списку клубов',
                        'callback_data' => 'back_to_admin_list',
                    ]],
                ]
            );
            return;
        }

        $speakingClub = $this->speakingClubRepository->findById($participation->getSpeakingClubId());
        if ($speakingClub === null) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб не найден',
                replyMarkup: [
                    [[
                        'text' => '<< Вернуться к списку клубов',
                        'callback_data' => 'back_to_admin_list',
                    ]],
                ]
            );
            return;
        }

        $availablePlacesCount = $speakingClub->getMaxParticipantsCount() -
            $this->participationRepository->countByClubId($speakingClub->getId());
        if ($availablePlacesCount <= 0) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Нет свободных мест',
                replyMarkup: [
                    [[
                        'text' => '<< Вернуться к списку участников',
                        'callback_data' => sprintf(
                            'show_participants:%s',
                            $speakingClub->getId()->toString()
                        ),
                    ]],
                ]
            );
        }

        $user = $this->userQuery->findById($participation->getUserId());

        if ($participation->isPlusOne() === true) {
            $this->telegram->editMessageText(
                $command->chatId,
                $command->messageId,
                'Участник уже имеет +1',
                replyMarkup: [
                    [[
                        'text' => '<< Вернуться к списку участников',
                        'callback_data' => sprintf(
                            'show_participants:%s',
                            $speakingClub->getId()->toString()
                        ),
                    ]],
                ]
            );
            return;
        }

        $participation->setIsPlusOne(true);
        $this->participationRepository->save($participation);

        // Notify admin

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Участнику добавлен +1',
            replyMarkup: [
                [[
                    'text' => '<< Вернуться к списку участников',
                    'callback_data' => sprintf(
                        'show_participants:%s',
                        $speakingClub->getId()->toString()
                    ),
                ]],
            ]
        );

        // Notify user

        if ($user !== null) {
            $this->telegram->sendMessage(
                chatId: $user->chatId,
                text: sprintf(
                    'Администратор добавил вам +1 к участию в клубе "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i')
                ),
                replyMarkup: [
                    [[
                        'text' => 'Посмотреть информацию о клубе',
                        'callback_data' => sprintf(
                            'show_speaking_club:%s',
                            $speakingClub->getId()->toString()
                        ),
                    ]],
                ]
            );
        }
    }
}
