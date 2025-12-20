<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Callback\Admin;

use App\SpeakingClub\Domain\Participation;
use App\SpeakingClub\Domain\ParticipationRepository;
use App\Tests\Shared\BaseApplicationTest;
use App\User\Infrastructure\Doctrine\Fixtures\UserFixtures;
use Exception;
use Ramsey\Uuid\Uuid;

class AdminShowSpeakingClubWithPlusOneNameTest extends BaseApplicationTest
{
    /**
     * @throws Exception
     */
    public function testShowsPlusOneName(): void
    {
        $speakingClub = $this->createSpeakingClub();

        // Создаем участие с +1 и именем
        /** @var ParticipationRepository $participationRepository */
        $participationRepository = self::getContainer()->get(ParticipationRepository::class);
        $participation = new Participation(
            id: $this->uuidProvider->provide(),
            userId: Uuid::fromString(UserFixtures::USER_ID_JOHN_CONNNOR),
            speakingClubId: $speakingClub->getId(),
            isPlusOne: true,
            plusOneName: 'Иван Иванов',
        );
        $participationRepository->save($participation);

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::ADMIN_CHAT_ID,
            messageId: 123,
            callbackData: 'admin_show_speaking_club:' . $speakingClub->getId()
        );

        $this->assertArrayHasKey(UserFixtures::ADMIN_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::ADMIN_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::ADMIN_CHAT_ID, self::MESSAGE_ID);

        // Должно отображаться (+1 Иван Иванов) вместо просто (+1)
        self::assertStringContainsString('@john_connor (+1 Иван Иванов)', $message['text']);
    }

    /**
     * @throws Exception
     */
    public function testShowsPlusOneWithoutName(): void
    {
        $speakingClub = $this->createSpeakingClub();

        // Создаем участие с +1 без имени (старые записи)
        $this->createParticipation(
            $speakingClub->getId(),
            UserFixtures::USER_ID_JOHN_CONNNOR,
            true
        );

        $this->sendWebhookCallbackQuery(
            chatId: UserFixtures::ADMIN_CHAT_ID,
            messageId: 123,
            callbackData: 'admin_show_speaking_club:' . $speakingClub->getId()
        );

        $this->assertArrayHasKey(UserFixtures::ADMIN_CHAT_ID, $this->getMessages());
        $messages = $this->getMessagesByChatId(UserFixtures::ADMIN_CHAT_ID);

        $this->assertArrayHasKey(self::MESSAGE_ID, $messages);
        $message = $this->getMessage(UserFixtures::ADMIN_CHAT_ID, self::MESSAGE_ID);

        // Должно отображаться просто (+1) если имя не указано
        self::assertStringContainsString('@john_connor (+1)', $message['text']);
    }
}
