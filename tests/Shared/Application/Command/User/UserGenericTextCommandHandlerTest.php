<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Command\User;

use App\SpeakingClub\Domain\ParticipationRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Domain\UserRepository;
use App\User\Domain\UserStateEnum;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;

class UserGenericTextCommandHandlerTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testSuccessWhenReceivingPlusOneName(): void
    {
        $speakingClub = $this->createSpeakingClub();

        $participation = $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR
        );

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        $user->setState(UserStateEnum::RECEIVING_PLUS_ONE_NAME);
        $user->setActualSpeakingClubData([
            'speakingClubId' => $speakingClub->getId()->toString(),
        ]);
        $userRepository->save($user);

        // Отправляем имя
        $this->sendWebhookMessage(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            text: 'Петр Сидоров'
        );

        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        $lastMessage = end($messages);

        self::assertEquals(
            <<<HEREDOC
👌 Участник добавлен: Петр Сидоров
HEREDOC,
            $lastMessage['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ваших клубов',
                    'callback_data' => 'back_to_my_list',
                ]
            ],
        ], $lastMessage['replyMarkup']);

        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $updatedParticipation = $participationRepository->findById($participation->getId());

        self::assertNotNull($updatedParticipation);
        self::assertTrue($updatedParticipation->isPlusOne());
        self::assertEquals('Петр Сидоров', $updatedParticipation->getPlusOneName());

        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        self::assertEquals('IDLE', $user->getState()->value);
        self::assertEquals([], $user->getActualSpeakingClubData());
    }

    /**
     * @throws Exception
     */
    public function testClubAlreadyPassedWhenReceivingPlusOneName(): void
    {
        // Создаем клуб с прошедшей датой
        $speakingClub = $this->createSpeakingClub(
            date: date('Y-m-d H:i:s', strtotime('-1 day'))
        );

        // Создаем участие с +1
        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR,
            isPlusOne: true,
            plusOneName: null,
        );

        // Напрямую устанавливаем состояние пользователя в БД
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        $user->setState(UserStateEnum::RECEIVING_PLUS_ONE_NAME);
        $user->setActualSpeakingClubData([
            'speakingClubId' => $speakingClub->getId()->toString(),
        ]);
        $userRepository->save($user);

        // Отправляем имя
        $this->sendWebhookMessage(
            chatId: UserFixtures::USER_CHAT_ID_JOHN_CONNNOR,
            text: 'Петр Сидоров'
        );

        $messages = $this->getMessagesByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        $lastMessage = end($messages);

        self::assertEquals(
            <<<HEREDOC
🤔 К сожалению, этот разговорный клуб уже прошел
HEREDOC,
            $lastMessage['text']
        );

        self::assertEquals([
            [
                [
                    'text'          => '<< Перейти к списку ближайших клубов',
                    'callback_data' => 'back_to_list',
                ]
            ],
        ], $lastMessage['replyMarkup']);

        // Проверяем, что состояние пользователя сброшено
        $user = $userRepository->findByChatId(UserFixtures::USER_CHAT_ID_JOHN_CONNNOR);
        self::assertEquals('IDLE', $user->getState()->value);
    }
}
