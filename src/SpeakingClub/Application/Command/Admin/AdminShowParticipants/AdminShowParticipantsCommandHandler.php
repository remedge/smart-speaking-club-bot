<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\Admin\AdminShowParticipants;

use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Query\ParticipationQuery;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AdminShowParticipantsCommandHandler
{
    public function __construct(
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationQuery $participationQuery,
        private TelegramInterface $telegram,
    ) {
    }

    public function __invoke(AdminShowParticipantsCommand $command): void
    {
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);
        $participants = $this->participationQuery->findBySpeakingClubId($speakingClub->getId());

        $buttons = [];

        foreach ($participants as $participant) {
            $buttons[] = [
                [
                    'text' => sprintf(
                        '%s %s (@%s) - Убрать',
                        $participant->firstName,
                        $participant->lastName,
                        $participant->username,
                    ),
                    'callback_data' => sprintf('remove_participant:%s', $participant->id->toString()),
                ],
                $participant->isPlusOne === true ? [
                    'text' => 'Убрать +1',
                    'callback_data' => sprintf(
                        'admin_remove_plus_one:%s',
                        $participant->id->toString()
                    ),
                ] : [
                    'text' => 'Добавить +1',
                    'callback_data' => sprintf(
                        'admin_add_plus_one:%s',
                        $participant->id->toString()
                    ),
                ],
            ];
        }

        $buttons[] = [[
            'text' => 'Добавить участника',
            'callback_data' => sprintf('admin_add_participant:%s', $speakingClub->getId()->toString()),
        ]];

        $buttons[] = [[
            'text' => '<< Перейти описанию клуба',
            'callback_data' => sprintf('admin_show_speaking_club:%s', $speakingClub->getId()->toString()),
        ]];

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: sprintf(
                'Список участников клуба "%s" %s. Вы можете добавить или убрать участника а также добавить или убрать +1 с ним',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y H:i')
            ),
            replyMarkup: $buttons
        );
    }
}
