<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\SignOutApply;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Event\SpeakingClubFreeSpaceAvailableEvent;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use App\UserBan\Domain\UserBan;
use App\UserBan\Domain\UserBanRepository;
use App\UserWarning\Domain\UserWarning;
use App\UserWarning\Domain\UserWarningRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SignOutApplyCommandHandler
{
    public function __construct(
        private UserQuery $userQuery,
        private ParticipationRepository $participationRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private UserWarningRepository $userWarningRepository,
        private UserBanRepository $userBanRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private EventDispatcherInterface $eventDispatcher,
        private Clock $clock,
    ) {
    }

    public function __invoke(SignOutApplyCommand $command): void
    {
        $user = $this->userQuery->getByChatId($command->chatId);
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Разговорный клуб не найден',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        if ($this->clock->now() > $speakingClub->getDate()) {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '🤔 К сожалению, этот разговорный клуб уже прошел',
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
        if ($participation === null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: '🤔 Вы не записаны на этот клуб',
                replyMarkup: [[
                    [
                        'text' => '<< Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_list',
                    ],
                ]]
            );
            return;
        }

        $this->participationRepository->remove($participation);

        $userWarning = $this->userWarningRepository->findUserUpcoming($user->id, $this->clock->now()->modify('-1 month'));
        $endDate = $this->clock->now()->modify('+1 week');

        if (count($userWarning) > 0) {
            $this->userBanRepository->save(new UserBan(
                id: $this->uuidProvider->provide(),
                userId: $user->id,
                endDate: $endDate,
                createdAt: $this->clock->now(),
            ));
        }
        $this->userWarningRepository->save(new UserWarning(
            id: $this->uuidProvider->provide(),
            userId: $user->id,
            createdAt: $this->clock->now(),
        ));

        $this->telegram->editMessageText(
            chatId: $command->chatId,
            messageId: $command->messageId,
            text: '👌 Вы успешно отписаны от разговорного клуба',
            replyMarkup: [[
                [
                    'text' => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ],
            ]]
        );

        $this->eventDispatcher->dispatch(new SpeakingClubFreeSpaceAvailableEvent($speakingClub->getId()));
    }
}
