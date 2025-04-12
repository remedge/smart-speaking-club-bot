<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\GenericText;

use App\BlockedUser\Domain\BlockedUser;
use App\BlockedUser\Domain\BlockedUserRepository;
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
use App\User\Application\Command\Admin\Notifications\SendMessageToAllUsersCommand;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\UserBan\Domain\UserBan;
use App\UserBan\Domain\UserBanRepository;
use App\UserWarning\Domain\UserWarning;
use App\UserWarning\Domain\UserWarningRepository;
use App\WaitList\Domain\WaitingUserRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
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
        private UserBanRepository $userBanRepository,
        private UserWarningRepository $userWarningRepository,
        private BlockedUserRepository $blockedUserRepository,
        private MessageBusInterface $commandBus,
        private LoggerInterface $logger
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

            $user->setState(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                $command->chatId,
                'Введите username преподавателя(без @) или "пропустить" чтобы пропустить'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_CREATION) {
            if ('пропустить' !== trim(mb_strtolower($command->text))) {
                $data = $user->getActualSpeakingClubData();
                $data['teacher_username'] = $command->text;
                $user->setActualSpeakingClubData($data);
            }

            $user->setState(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                $command->chatId,
                'Введите ссылку на разговорный клуб или "пропустить" чтобы пропустить'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_CREATION) {
            if ('пропустить' !== trim(mb_strtolower($command->text))) {
                $data = $user->getActualSpeakingClubData();
                $data['link'] = $command->text;
                $user->setActualSpeakingClubData($data);
            }

            $user->setState(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_CREATION);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                $command->chatId,
                'Введите минимальное количество участников'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_CREATION) {
            if (!is_int((int)$command->text) || (int)$command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['min_participants_count'] = (int)$command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_CREATION) {
            if (!is_int((int)$command->text) || (int)$command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int)$command->text;

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
                minParticipantsCount: $data['min_participants_count'],
                maxParticipantsCount: $data['max_participants_count'],
                date: $date,
                link: $data['link'],
                teacherUsername: $data['teacher_username'],
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
                    [
                        [
                            'text'          => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ]
                    ],
                    [
                        [
                            'text'          => 'Создать еще один клуб',
                            'callback_data' => 'admin_create_club',
                        ]
                    ],
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

            $user->setState(UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                $command->chatId,
                'Введите новый username преподавателя(без @) или "пропустить" чтобы пропустить'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_TEACHER_USERNAME_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['teacher_username'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                $command->chatId,
                'Введите новую ссылку на разговорный клуб или "пропустить" чтобы пропустить'
            );
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_LINK_TO_CLUB_FOR_EDITING) {
            $data = $user->getActualSpeakingClubData();
            $data['link'] = $command->text;

            $user->setState(UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое минимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MIN_PARTICIPANTS_COUNT_FOR_EDITING) {
            if (!is_int((int)$command->text) || (int)$command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['min_participants_count'] = (int)$command->text;

            $user->setState(UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING);
            $user->setActualSpeakingClubData($data);
            $this->userRepository->save($user);

            $this->telegram->sendMessage($command->chatId, 'Введите новое максимальное количество участников');
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_MAX_PARTICIPANTS_COUNT_FOR_EDITING) {
            if (!is_int((int)$command->text) || (int)$command->text <= 0) {
                $this->telegram->sendMessage($command->chatId, 'Введите целое число больше 0');
                return;
            }

            $currentParticipationsCount = $this->participationRepository->countByClubId(
                Uuid::fromString($user->getActualSpeakingClubData()['id'])
            );

            if ($currentParticipationsCount > (int)$command->text) {
                $this->telegram->sendMessage(
                    $command->chatId,
                    'На текущий момент в клубе уже есть участники, ' .
                    'поэтому максимальное количество участников не может быть меньше текущего. Попробуйте еще раз'
                );
                return;
            }

            $data = $user->getActualSpeakingClubData();
            $data['max_participants_count'] = (int)$command->text;

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
            $oldData = [
                'id'                     => $speakingClub->getId()->toString(),
                'name'                   => $speakingClub->getName(),
                'description'            => $speakingClub->getDescription(),
                'teacher_username'       => $speakingClub->getTeacherUsername(),
                'link'                   => $speakingClub->getLink(),
                'min_participants_count' => $speakingClub->getMinParticipantsCount(),
                'max_participants_count' => $speakingClub->getMaxParticipantsCount(),
                'date'                   => $speakingClub->getDate()->format('d.m.Y H:i'),
            ];
            $speakingClub->setName($data['name']);
            $speakingClub->setDescription($data['description']);
            $speakingClub->setTeacherUsername($data['teacher_username']);
            $speakingClub->setLink($data['link']);
            $speakingClub->setMinParticipantsCount((int)$data['min_participants_count']);

            if ($speakingClub->getMaxParticipantsCount() < $data['max_participants_count']) {
                $this->eventDispatcher->dispatch(new SpeakingClubFreeSpaceAvailableEvent($speakingClub->getId()));
            }

            $speakingClub->setMaxParticipantsCount((int)$data['max_participants_count']);

            if ($speakingClub->getDate()->format('d.m.Y H:i') !== $date->format('d.m.Y H:i')) {
                $this->eventDispatcher->dispatch(
                    new SpeakingClubScheduleChangedEvent($speakingClub->getId(), $date->format('d.m.Y H:i'))
                );
            }
            $speakingClub->setDate($date);

            $this->speakingClubRepository->save($speakingClub);

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: 'Клуб успешно изменен',
                replyMarkup: [
                    [
                        [
                            'text'          => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]
                ],
            );

            $this->logger->info(
                'The speaking club has been changed.',
                [
                    'admin_user_name' => $user->getUsername(),
                    'admin_chat_id'   => $command->chatId,
                    'old_data'        => $oldData,
                    'new_data'        => array_merge($data, ['date' => $command->text])
                ]
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
                        [
                            [
                                'text'          => '<< Вернуться к списку участников',
                                'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                            ]
                        ],
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
                        [
                            [
                                'text'          => '<< Вернуться к списку участников',
                                'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                            ]
                        ],
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
                        [
                            [
                                'text'          => '<< Вернуться к списку участников',
                                'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                            ]
                        ],
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
                        [
                            [
                                'text'          => '<< Вернуться к списку участников',
                                'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                            ]
                        ],
                    ],
                );
                return;
            }

            $this->participationRepository->save(
                new Participation(
                    id: $this->uuidProvider->provide(),
                    userId: $participantUser->getId(),
                    speakingClubId: $speakingClubId,
                    isPlusOne: false,
                )
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно добавлен в клуб',
                replyMarkup: [
                    [
                        [
                            'text'          => 'Перейти к списку участников',
                            'callback_data' => sprintf('show_participants:%s', $speakingClubId->toString()),
                        ]
                    ],
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
                    [
                        [
                            'text'          => 'Перейти к описанию клуба',
                            'callback_data' => sprintf('show_speaking_club:%s', $speakingClubId->toString()),
                        ]
                    ],
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
            $this->commandBus->dispatch(
                new SendMessageToAllUsersCommand($command->text, $command->chatId)
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '✅ Сообщение отправлено в очередь рассылки всем пользователям',
                replyMarkup: [
                    [
                        [
                            'text'          => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]
                ],
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
                    replyMarkup: [
                        [
                            [
                                'text'          => 'Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_admin_list',
                            ],
                        ]
                    ],
                );
                return;
            }

            $participations = $this->participationRepository->findBySpeakingClubId(Uuid::fromString($speakingClubId));

            foreach ($participations as $recipient) {
                $this->telegram->sendMessage(
                    chatId: (int)$recipient['chatId'],
                    text: $command->text,
                );
            }

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            $this->telegram->sendMessage(
                chatId: $command->chatId,
                text: '✅ Сообщение успешно отправлено всем участникам клуба',
                replyMarkup: [
                    [
                        [
                            'text'          => 'Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]
                ],
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
                    replyMarkup: [
                        [
                            [
                                'text'          => 'Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_admin_list',
                            ],
                        ]
                    ],
                );
                return;
            }

            $speakingClub = $this->speakingClubRepository->findById($speakingClubId);
            if ($speakingClub === null) {
                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Клуб не найден',
                    replyMarkup: [
                        [
                            [
                                'text'          => '<< Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_admin_list',
                            ],
                        ]
                    ]
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
                    replyMarkup: [
                        [
                            [
                                'text'          => '<< Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_admin_list',
                            ],
                        ]
                    ]
                );
                return;
            }

            // TODO: move to participation domain

            $participants = $this->participationRepository->findBySpeakingClubId($speakingClubId);
            foreach ($participants as $participant) {
                $this->telegram->sendMessage(
                    chatId: (int)$participant['chatId'],
                    text: sprintf(
                        'К сожалению, клуб "%s" %s был отменен',
                        $speakingClub->getName(),
                        $speakingClub->getDate()->format('d.m.Y H:i')
                    ),
                    replyMarkup: [
                        [
                            [
                                'text'          => 'Перейти к списку ближайших клубов',
                                'callback_data' => 'back_to_list',
                            ],
                        ]
                    ]
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
                        replyMarkup: [
                            [
                                [
                                    'text'          => 'Перейти к списку ближайших клубов',
                                    'callback_data' => 'back_to_admin_list',
                                ],
                            ]
                        ]
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
                replyMarkup: [
                    [
                        [
                            'text'          => '<< Перейти к списку ближайших клубов',
                            'callback_data' => 'back_to_admin_list',
                        ],
                    ]
                ]
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_ADD_BAN) {
            $participantUser = $this->userRepository->findByUsername($command->text);

            if ($participantUser === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Такого пользователя нет в базе бота',
                );
                return;
            }

            if ($this->userRolesProvider->isUserAdmin($participantUser->getUsername())) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Нельзя забанить администратора',
                );
                return;
            }

            $endDate = $this->clock->now()->modify('+1 week');

            $this->userBanRepository->save(
                new UserBan(
                    id: $this->uuidProvider->provide(),
                    userId: $participantUser->getId(),
                    endDate: $endDate,
                    createdAt: $this->clock->now(),
                )
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно забанен',
            );

            // Notify user
            /*$this->telegram->sendMessage(
                chatId: $participantUser->getChatId(),
                text: sprintf(
                    'Администратор забанил вас "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i'),
                ),
            );*/
            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_USERNAME_TO_BLOCK) {
            $userToBlock = $this->userRepository->findByUsername($command->text);

            if ($userToBlock === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Такого пользователя нет в базе бота',
                );
                return;
            }

            if ($this->userRolesProvider->isUserAdmin($userToBlock->getUsername())) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Нельзя заблокировать администратора',
                );
                return;
            }

            $this->blockedUserRepository->save(
                new BlockedUser(
                    id: $this->uuidProvider->provide(),
                    userId: $userToBlock->getId(),
                    createdAt: $this->clock->now(),
                )
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно заблокирован',
            );

            return;
        }

        if ($user->getState() === UserStateEnum::RECEIVING_ADD_WARNING) {
            $participantUser = $this->userRepository->findByUsername($command->text);

            if ($participantUser === null) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Такого пользователя нет в базе бота',
                );
                return;
            }

            if ($this->userRolesProvider->isUserAdmin($participantUser->getUsername())) {
                $user->setState(UserStateEnum::IDLE);
                $user->setActualSpeakingClubData([]);
                $this->userRepository->save($user);

                $this->telegram->sendMessage(
                    chatId: $command->chatId,
                    text: 'Нельзя добавить в список предупреждения администратора',
                );
                return;
            }

            $this->userWarningRepository->save(
                new UserWarning(
                    id: $this->uuidProvider->provide(),
                    userId: $participantUser->getId(),
                    createdAt: $this->clock->now(),
                )
            );

            $user->setState(UserStateEnum::IDLE);
            $user->setActualSpeakingClubData([]);
            $this->userRepository->save($user);

            // Notify admin
            $this->telegram->sendMessage(
                chatId: $user->getChatId(),
                text: 'Пользователь успешно добавлен в список предупреждения',
            );

            // Notify user
            /*$this->telegram->sendMessage(
                chatId: $participantUser->getChatId(),
                text: sprintf(
                    'Администратор забанил вас "%s" %s',
                    $speakingClub->getName(),
                    $speakingClub->getDate()->format('d.m.Y H:i'),
                ),
            );*/
            return;
        }

        $this->telegram->sendMessage(
            chatId: $command->chatId,
            text: 'К сожалению я пока просто робот и могу понимать только команды через кнопки 🤖

Если вы хотите узнать больше о том, что я могу, нажмите /help.
А если хотите пообщаться с администратором, напишите пожалуйста @SmartLab_NoviSad 😊',
        );

        $this->logger->info('Admin state not found in the list.', [
            'adminChatId' => $command->chatId,
            'adminState'  => $user->getState(),
            'text'        => $command->text,
        ]);
    }
}
