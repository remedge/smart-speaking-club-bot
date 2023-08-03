<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

use App\Shared\Application\Clock;
use App\Shared\Application\UuidProvider;
use App\Shared\Domain\TelegramInterface;
use App\Shared\Domain\UserRolesProvider;
use App\SpeakingClub\Application\Event\SpeakingClubFreeSpaceAvailableEvent;
use App\SpeakingClub\Application\Event\SpeakingClubScheduleChangedEvent;
use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\SpeakingClub\Domain\SpeakingClub;
use App\SpeakingClub\Domain\SpeakingClubRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
class AdminGenericTextCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private SpeakingClubRepository $speakingClubRepository,
        private TelegramInterface $telegram,
        private UuidProvider $uuidProvider,
        private ParticipationRepository $participationRepository,
        private Clock $clock,
        private EventDispatcherInterface $eventDispatcher,
        private UserRolesProvider $userRolesProvider,
        private WaitingUserRepository $waitingUserRepository,
    ) {
    }

    public function __invoke(AdminGenericTextCommand $command): void
    {
        $user = $this->userRepository->findByChatId($command->chatId);
        if ($user === null) {
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_NAME_FOR_CREATING) {
            $data = [];
            $data['name'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите описание клуба');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DESCRIPTION_FOR_CREATION) {
            $data = $user->getActualSpeakingClubData();
            $data['description'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION) {
            if (!is_int((int) $command->text) || (int) $command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int) $command->text;

            $user->setState(UserStateEnum::RECEIVING_DATE_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите дату проведения в формате: 15.10.2023 10:00');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DATE_FOR_CREATION) {
            $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $command->text);

            if ($date < $this->clock->now()) {
                $this->telegram->sendMessage($command->chatId, 'Дата не может быть в прошлом, попробуйте еще раз');
                return;
            }

            if ($date === false) {
                $this->telegram->sendMessage($command->chatId, 'Некорректная дата, попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $speakingClub = new SpeakingClub(
                id: $this->uuidProvider->provide(),
                name: $data['name'],
                description: $data['description'],
                maxParticipantsCount: $data['max_participants_count'],
                date: $date,
            );
            try {
                $this->speakingClubRepository->save($speakingClub);
            } catch (Throwable $e) {
                $this->telegram->sendMessage($command->chatId, 'Что-то пошло не так, попробуйте еще раз');
                return;
            }

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно создан',
                replyMarkup: [
                    [[
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ]],
                    [[
                        'text' => 'Создать еще один клуб',
                        'callback_data' => 'admin_create_club',
                    ]],
                ],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_NAME_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['name'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_DESCRIPTION_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое описание клуба');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DESCRIPTION_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['description'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING) {
            if (!is_int((int) $command->text) || (int) $command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $currentParticipationsCount = $this->participationRepository->countByClubId(
                Uuid::fromString($user->getActualSpeakingClubData()['id'])
            );

            if ($currentParticipationsCount > (int) $command->text) {
                $this->telegram->sendMessage($command->chatId, 'На текущий момент в клубе уже есть участники, ' .
                    'поэтому максимальное количество участников не может быть меньше текущего. Попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int) $command->text;

            $user->setState(UserStateEnum::RECEIVING_DATE_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новую дату проведения в формате: 15.10.2023 10:00');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_DATE_FOR_EDITING) {
            $date = DateTimeImmutable::createFromFormat('d.m.Y H:i', $command->text);

            if ($date < $this->clock->now()) {
                $this->telegram->sendMessage($command->chatId, 'Дата не может быть в прошлом, попробуйте еще раз');
                return;
            }

            if ($date === false) {
                $this->telegram->sendMessage($command->chatId, 'Некорректная дата, попробуйте еще раз');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $speakingClub = $this->speakingClubRepository->findById(Uuid::fromString($data['id']));
            if ($speakingClub === null) {
                $this->telegram->sendMessage($command->chatId, 'Редактируемый Клуб не найден');
                return;
            }
            $speakingClub->setName($data['name']);
            $speakingClub->setDescription($data['description']);

            if ($speakingClub->getMaxParticipantsCount() < $data['max_participants_count']) {
                $this->eventDispatcher->dispatch(new SpeakingClubFreeSpaceAvailableEvent($speakingClub->getId()));
            }

            $speakingClub->setMaxParticipantsCount((int) $data['max_participants_count']);

            if ($speakingClub->getDate() !== $date) {
                $this->eventDispatcher->dispatch(new SpeakingClubScheduleChangedEvent($speakingClub->getId()));
            }
            $speakingClub->setDate($date);

            $this->speakingClubRepository->save($speakingClub);

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно изменен',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_PARTICIPANT) {
            $speakingClubId = Uuid::fromString($user->getActualSpeakingClubData()['participantSpeakingClubId']);

            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $this->telegram->sendMessage($command->chatId, 'Клуб не найден');
                return;
            }

            $availableSpaceCount = $speakingClub->getMaxParticipantsCount() -
                $this->participationRepository->countByClubId($speakingClubId);
            if ($availableSpaceCount <= 0) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'В клубе нет свободных мест',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $participantUser = $this->userRepository->findByUsername($command->text);
            if ($participantUser === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Такого пользователя нет в базе бота',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            if ($this->userRolesProvider->isUserAdmin($participantUser->getUsername())) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Нельзя добавить администратора в клуб',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $participation = $this->participationRepository->findByUserIdAndSpeakingClubId(
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId
            );
            if ($participation !== null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Пользователь уже участвует в клубе',
                    replyMarkup: [
                        [[
                            'text' => '<< Вернуться к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]],
                    ],
                );
                return;
            }

            $this->participationRepository->save(new Participation(
                id: $this->uuidProvider->provide(),
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId,
                isPlusOne: false,
            ));

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно добавлен в клуб',
                replyMarkup: [
                    [[
                        'text' => 'Перейти к списку участников',
                        'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                    ]],
                ],
            );

            // Notify user
            $this->telegram->sendMessage(
                chatId: $participantUser->getChatId(),
                text: sprintf(
                    'Администратор добавил вас в клуб "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i'),
                ),
                replyMarkup: [
                    [[
                        'text' => 'Перейти к описанию клуба',
                        'callback_data' => sprintf('show_speaking_club:%s', $speakingClubId->toString()),
                    ]],
                ],
            );

            // Remove user from waiting list
            $waitingUser = $this->waitingUserRepository->findOneByUserIdAndSpeakingClubId(
                userId: $participantUser->getId(),
                speakingClubId: $speakingClubId
            );

            if ($waitingUser !== null) {
                $waitingUserEntity = $this->waitingUserRepository->findById($waitingUser['id']); // TODO: rewrite it
                if ($waitingUserEntity !== null) {
                    $this->waitingUserRepository->remove($waitingUserEntity);
                }
            }
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MESSAGE_FOR_EVERYONE) {
            $recipients = $this->userRepository->findAllExceptUsernames($this->userRolesProvider->getAdminUsernames());

            foreach ($recipients as $recipient) {
                $this->telegram->sendMessage(
                    chatId: $recipient->getChatId(),
                    text: $command->text,
                );
            }

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '✅ Сообщение успешно отправлено всем пользователям',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MESSAGE_FOR_PARTICIPANTS) {
            $speakingClubId = $user->getActualSpeakingClubData()['id'] ?? null;

            if ($speakingClubId === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Что-то пошло не так, попробуйте еще раз',
                    replyMarkup: [[
                        [
                            'text' => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]],
                );
                return;
            }

            $participations = $this->participationRepository->findBySpeakingClubId(Uuid::fromString($speakingClubId));

            foreach ($participations as $recipient) {
                $this->telegram->sendMessage(
                    chatId: (int) $recipient['chatId'],
                    text: $command->text,
                );
            }

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '✅ Сообщение успешно отправлено всем участникам клуба',
                replyMarkup: [[
                    [
                        'text' => 'Перейти к списку ближайших клубов',
                        'callback_data' => 'back_to_admin_list',
                    ],
                ]],
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_CONFIRMATION_CLUB_CANCELLATION) {
            $speakingClubId = $user->getActualSpeakingClubData()['id'] ?? null;

            if ($speakingClubId === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Что-то пошло не так, попробуйте еще раз',
                    replyMarkup: [[
                        [
                            'text' => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]],
                );
                return;
            }

            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Клуб не найден',
                    replyMarkup: [[
                        [
                            'text' => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]]
                );
                return;
            }

            if ($speakingClub->getName() !== $command->text) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Введенное название не совпадает с выбранным клубом 🤨, попробуйте еще раз или введите /skip для отмены',
                );
                return;
            }

            if ($speakingClub->isCancelled() === true) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
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

            // TODO: move to participation domain

            $participants = $this->participationRepository->findBySpeakingClubId($speakingClubId);
            foreach ($participants as $participant) {
                $this->telegram->sendMessage(
                    chatId: (int) $participant['chatId'],
                    text: sprintf(
                        'К сожалению, клуб "%s" %s был отменен',
                        $speakingClub->getName(),
                        $speakingClub->getDate()->format('d.m.Y H:i')
                    ),
                    replyMarkup: [[
                        [
                            'text' => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_list',
                        ],
                    ]]
                );
            }

            // TODO: move  to waitlist domain

            $waitingUsers = $this->waitingUserRepository->findBySpeakingClubId($speakingClub->getId());
            foreach ($waitingUsers as $waitingUser) {
                $user = $this->userRepository->findById($waitingUser['userId']); // TODO: rewrite it
                if ($user !== null) {
                    $this->telegram->sendMessage(
                        chatId: $user->getChatId(),
                        text: sprintf(
                            'К сожалению, клуб "%s" %s был отменен',
                            $speakingClub->getName(),
                            $speakingClub->getDate()->format('d.m.Y H:i')
                        ),
                        replyMarkup: [[
                            [
                                'text' => 'Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_admin_list',
                            ],
                        ]]
                    );
                }

                $waitingUserEntity = $this->waitingUserRepository->findById($waitingUser['id']);
                if ($waitingUserEntity !== null) {
                    $this->waitingUserRepository->remove($waitingUserEntity);
                }
            }

            $speakingClub->cancel();
            $this->speakingClubRepository->save($speakingClub);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
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

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            return;
        }

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'К сожалению я пока просто робот и могу понимать только команды через кнопки 🤖

Если вы хотите узнать больше о том, что я могу, нажмите /help.
А если хотите пообщаться с администратором, напишите пожалуйста @SmartLab_NoviSad 😊',
        );
    }
}
