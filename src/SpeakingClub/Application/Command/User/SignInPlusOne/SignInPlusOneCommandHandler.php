<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignInPlusOne;

use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use App\WaitList\Application\Command\LeaveWaitingList\LeaveWaitingListCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SignInPlusOneCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(SignInPlusOneCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Клуб не найден',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId($user->id, $command->speakingClubId);
        if ($participation !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Вы уже записаны на клуб',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $participationCount = $this->participationRepository->countByClubId($command->speakingClubId);
        if (($participationCount + 1) >= $speakingClub->getMaxParticipantsCount()) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Все места на данное мероприятие заняты',
                replyMarkup: [
                    [[
                        'text' => 'Встать в лист ожидания',
                        'callback_data' => sprintf(
                            'join_waiting_list:%s',
                            $command->speakingClubId->toString()
                        ),
                    ]],
                    [[
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ]],
                ]
            );
            return;
        }

        $this->participationRepository->save(new Participation(
            id: $this->uuidProvider->provide(),
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
            isPlusOne: true,
        ));

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: 'Вы успешно записаны на клуб c +1 человеком',
            replyMarkup: [[
                [
                    'text' => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ],
            ]]
        );

        $this->messageBus->dispatch(new LeaveWaitingListCommand(
            chatId: $command->chatId,
            speakingClubId: $command->speakingClubId,
        ));
    }
}
