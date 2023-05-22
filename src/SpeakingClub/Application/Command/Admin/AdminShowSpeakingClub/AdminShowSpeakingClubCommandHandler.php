<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminShowSpeakingClub;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Query\ParticipationQuery;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\WaitList\Domain\WaitingUserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminShowSpeakingClubCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private ParticipationQuery $participationQuery,
        private WaitingUserRepository $waitingUserRepository,
    ) {
    }

    public function __invoke(AdminShowSpeakingClubCommand $command): void
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

        $participations = $this->participationQuery->findBySpeakingClubId($speakingClub->getId());
        $totalParticipantsCount = $this->participationRepository->countByClubId($speakingClub->getId());

        $participants = '';
        foreach ($participations as $participation) {
            $participants .= '@' . $participation->username . ' ' . ($participation->isPlusOne ? '(+1)' : '') . PHP_EOL;
        }

        $waitingUsers = $this->waitingUserRepository->findBySpeakingClubId($speakingClub->getId());
        $waitingUsersString = '';
        foreach ($waitingUsers as $waitingUser) {
            $waitingUsersString .= '@' . $waitingUser['username'] . PHP_EOL;
        }

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: sprintf(
                'Название: %s'
                . PHP_EOL . 'Описание: %s'
                . PHP_EOL . 'Дата: %s'
                . PHP_EOL
                . PHP_EOL . 'Максимальное количество участников: %s'
                . PHP_EOL . 'Записалось участников: %s'
                . PHP_EOL
                . PHP_EOL . 'Список участников: ' . PHP_EOL . '%s'
                . PHP_EOL . 'Список ожидающих: ' . PHP_EOL . '%s',
                $speakingClub->getName(),
                $speakingClub->getDescription(),
                $speakingClub->getDate()->format('d.m.Y H:i'),
                $speakingClub->getMaxParticipantsCount(),
                $totalParticipantsCount,
                $participants === '' ? 'Нет участников' . PHP_EOL : $participants,
                $waitingUsersString === '' ? 'Нет ожидающих' : $waitingUsersString,
            ),
            replyMarkup: [
                [[
                    'text' => 'Редактировать данные клуба',
                    'callback_data' => sprintf('edit_club:%s', $speakingClub->getId()->toString()),
                ]],
                [[
                    'text' => 'Редактировать список участников клуба',
                    'callback_data' => sprintf('show_participants:%s', $speakingClub->getId()->toString()),
                ]],
                [[
                    'text' => 'Отменить разговорный клуб',
                    'callback_data' => sprintf('cancel_club:%s', $speakingClub->getId()->toString()),
                ]],
                [[
                    'text' => 'Отправить сообщение всем участникам клуба',
                    'callback_data' => sprintf('notify_participants:%s', $speakingClub->getId()->toString()),
                ]],
                [[
                    'text' => '<< Вернуться к списку клубов',
                    'callback_data' => 'back_to_admin_list',
                ]],
            ],
        );
    }
}
