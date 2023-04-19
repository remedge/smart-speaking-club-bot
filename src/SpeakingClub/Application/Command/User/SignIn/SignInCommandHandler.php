<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignIn;

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
class SignInCommandHandler
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

    public function __invoke(SignInCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'Разговорный клуб не найден',
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
                text: 'Вы уже записаны на этот разговорный клуб',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ваших клубов',
                        'callback_data' => 'back_to_my_list',
                    ],
                ]]
            );
            return;
        }

        $participationCount = $this->participationRepository->countByClubId($command->speakingClubId);
        if ($participationCount >= $speakingClub->getMaxParticipantsCount()) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: 'К сожалению, все свободные места на данное мероприятие заняты',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $this->participationRepository->save(new Participation(
            id: $this->uuidProvider->provide(),
            userId: $user->id,
            speakingClubId: $command->speakingClubId,
            isPlusOne: false,
        ));

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: sprintf(
                '👌 Вы успешно записаны на разговорный клуб "%s", который состоится %s в %s',
                $speakingClub->getName(),
                $speakingClub->getDate()->format('d.m.Y'),
                $speakingClub->getDate()->format('H:i'),
            ),
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
