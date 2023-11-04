<?php

declare(strict_types=1);

namespace App\SpeakingClub\Application\Command\User\ShowSpeakingClub;

use App\Shared\Application\Clock;
use App\Shared\Domain\TelegramInterface;
use App\SpeakingClub\Application\Command\User\AddPlusOne\AddPlusOneCommand;
use App\SpeakingClub\Application\Command\User\RemovePlusOne\RemovePlusOneCommand;
use App\SpeakingClub\Application\Command\User\SignIn\SignInCommand;
use App\SpeakingClub\Application\Command\User\SignInPlusOne\SignInPlusOneCommand;
use App\SpeakingClub\Application\Command\User\SignOut\SignOutCommand;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Application\Query\UserQuery;
use App\WaitList\Application\DTO\WaitingUserDTO;
use App\WaitList\Application\Query\WaitingUserQuery;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ShowSpeakingClubCommandHandler
{
    public function __construct(
        private TelegramInterface $telegram,
        private SpeakingClubRepository $speakingClubRepository,
        private ParticipationRepository $participationRepository,
        private UserQuery $userQuery,
        private WaitingUserQuery $waitingUserQuery,
        private Clock $clock,
    ) {
    }

    public function __invoke(ShowSpeakingClubCommand $command): void
    {
        $speakingClub = $this->speakingClubRepository->findById($command->speakingClubId);

        if ($speakingClub === null) {
            if ($command->messageId !== null) {
                $this->telegram->editMessageText(
                    chatId: $command->chatId,
                    messageId: $command->messageId,
                    text: '🤔 Такого клуба не существует',
                    replyMarkup: [[
                        [
                            'text' => '<< Перейти к списку ближайших клубов',
                            'callback_data' => $command->backCallback,
                        ],
                    ]]
                );
                return;
            } else {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: '🤔 Такого клуба не существует',
                    replyMarkup: [[
                        [
                            'text' => '<< Перейти к списку ближайших клубов',
                            'callback_data' => $command->backCallback,
                        ],
                    ]]
                );
                return;
            }
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

        $user = $this->userQuery->getByChatId($command->chatId);
        $participation = $this->participationRepository->findByUserIdAndSpeakingClubId(
            userId: $user->id,
            speakingClubId: $speakingClub->getId(),
        );
        $totalParticipantsCount = $this->participationRepository->countByClubId($speakingClub->getId());
        $waitingUser = $this->waitingUserQuery->findByUserIdAndSpeakingClubId(
            userId: $user->id,
            speakingClubId: $speakingClub->getId(),
        );

        $text = sprintf(
            'Название: %s'
            . PHP_EOL . 'Описание: %s'
            . PHP_EOL . 'Дата: %s'
            . PHP_EOL . 'Минимальное количество участников: %s'
            . PHP_EOL . 'Максимальное количество участников: %s'
            . PHP_EOL . 'Записалось участников: %s'
            . PHP_EOL
            . PHP_EOL . '%s'
            . PHP_EOL . '%s',
            $speakingClub->getName(),
            $speakingClub->getDescription(),
            $speakingClub->getDate()->format('d.m.Y H:i'),
            $speakingClub->getMinParticipantsCount(),
            $speakingClub->getMaxParticipantsCount(),
            $totalParticipantsCount,
            ($participation === null) ? 'Вы не записаны' : (($participation->isPlusOne() === true)
                ? 'Вы записаны с +1 человеком'
                : 'Вы записаны'),
            ($waitingUser === null) ? '' : 'Вы в списке ожидания',
        );
        $replyMarkup = $this->chooseButtonsByParticipation(
            participation: $participation,
            waitingUser: $waitingUser,
            speakingClubId: $speakingClub->getId(),
            availablePlacesCount: $speakingClub->getMaxParticipantsCount() - $totalParticipantsCount,
            backCallback: $command->backCallback,
        );

        if ($command->messageId !== null) {
            $this->telegram->editMessageText(
                chatId: $command->chatId,
                messageId: $command->messageId,
                text: $text,
                replyMarkup: $replyMarkup
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: $text,
                replyMarkup: $replyMarkup
            );
        }
    }

    /**
     * @return array<int, array<int, array<string, string>>>
     */
    private function chooseButtonsByParticipation(
        ?Participation $participation,
        ?WaitingUserDTO $waitingUser,
        UuidInterface $speakingClubId,
        int $availablePlacesCount,
        string $backCallback,
    ): array {
        $buttons = [];

        if ($participation === null) {
            if ($availablePlacesCount >= 1) {
                $buttons[] = [
                    [
                        'text' => 'Записаться',
                        'callback_data' => sprintf(
                            '%s:%s',
                            SignInCommand::CALLBACK_NAME,
                            $speakingClubId->toString()
                        ),
                    ],
                ];
            }
            if ($availablePlacesCount >= 2) {
                $buttons[] = [
                    [
                        'text' => 'Записаться с +1 человеком',
                        'callback_data' => sprintf(
                            '%s:%s',
                            SignInPlusOneCommand::CALLBACK_NAME,
                            $speakingClubId->toString()
                        ),
                    ],
                ];
            }
        } else {
            $buttons[] = [
                [
                    'text' => 'Отменить запись',
                    'callback_data' => sprintf(
                        '%s:%s',
                        SignOutCommand::CALLBACK_NAME,
                        $speakingClubId->toString()
                    ),
                ],
            ];
            if ($participation->isPlusOne() === true) {
                $buttons[] = [
                    [
                        'text' => 'Убрать +1 человека с собой',
                        'callback_data' => sprintf(
                            '%s:%s',
                            RemovePlusOneCommand::CALLBACK_NAME,
                            $speakingClubId->toString()
                        ),
                    ],
                ];
            } elseif ($availablePlacesCount >= 1) {
                $buttons[] = [
                    [
                        'text' => 'Добавить +1 человека с собой',
                        'callback_data' => sprintf(
                            '%s:%s',
                            AddPlusOneCommand::CALLBACK_NAME,
                            $speakingClubId->toString()
                        ),
                    ],
                ];
            }
        }

        if ($participation === null && $waitingUser === null && $availablePlacesCount === 0) {
            $buttons[] = [
                [
                    'text' => 'Встать в лист ожидания',
                    'callback_data' => 'join_waiting_list:' . $speakingClubId->toString(),
                ],
            ];
        }

        if ($waitingUser !== null) {
            $buttons[] = [
                [
                    'text' => 'Выйти из листа ожидания',
                    'callback_data' => 'leave_waiting_list:' . $speakingClubId->toString(),
                ],
            ];
        }

        $buttons[] = [
            [
                'text' => '<< Вернуться к списку клубов',
                'callback_data' => $backCallback,
            ],
        ];

        return $buttons;
    }
}
