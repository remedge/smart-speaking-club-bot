<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminRemovePlusOneToParticipant;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use App\WaitList\Application\Query\WaitingUserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminRemovePlusOneToParticipantCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private WaitingUserQuery $waitingUserQuery,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AdminRemovePlusOneToParticipantCommand $command): void
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

        $user = $this->userQuery->findById($participation->getUserId());

        if ($participation->isPlusOne() === false) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'У участника уже нет +1',
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

        $participation->setIsPlusOne(false);
        $this->participationRepository->save($participation);

        // Notify admin

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'У участника убран +1',
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
                    'Администратор убрал вам +1 к участию в клубе "%s" %s',
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

        // Notify waiting users

        $waitingUsers = $this->waitingUserQuery->findBySpeakingClubId($speakingClub->getId());
        foreach ($waitingUsers as $waitingUser) {
            $this->telegram->sendMessage(
                chatId: $waitingUser->chatId,
                text: sprintf(
                    'Появилось свободное место в клубе "%s" %s, спешите записаться!',
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
