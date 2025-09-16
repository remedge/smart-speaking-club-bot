<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminRemoveParticipant;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\System\DateHelper;
use App\User\Application\Query\UserQuery;
use App\WaitList\Application\Query\WaitingUserQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminRemoveParticipantCommandHandler
{
    public function __construct(
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private WaitingUserQuery $waitingUserQuery,
        private UserQuery $userQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AdminRemoveParticipantCommand $command): void
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

        $this->participationRepository->remove($participation);

        // Notify admin

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Пользователь успешно удален из списка участников',
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

        $user = $this->userQuery->findById($participation->getUserId());

        if ($user !== null) {
            $this->telegram->sendMessage(
                chatId: $user->chatId,
                text: sprintf(
                    'Администратор убрал вас из участников клуба "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i') . ' ' . DateHelper::getDayOfTheWeek(
                        $speakingClub->getDate()->format('d.m.Y')
                    )
                ),
                replyMarkup: [
                    [[
                        'text' => 'Посмотреть список ближайших клубов',
                        'callback_data' => 'back_to_list',
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
                    $speakingClub->getDate()->format('d.m.Y H:i') . ' ' . DateHelper::getDayOfTheWeek(
                        $speakingClub->getDate()->format('d.m.Y')
                    )
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
