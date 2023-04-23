<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Symfony\Component\HttpFoundation\Request;

interface TelegramInterface
{
    public function setWebhook(): string;

    public function parseUpdateFromRequest(Request $request): void;

    public function isCallbackQuery(): bool;

    public function getChatId(): int;

    public function getMessageId(): int;

    public function getText(): string;

    public function getFirstName(): string;

    public function getLastName(): string;

    public function getUsername(): string;

    /**
     * @param  array<int, array<int, array<string, string>>> $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, array $replyMarkup = []): void;

    /**
     * @param  array<int, array<int, array<string, string>>> $replyMarkup
     */
    public function editMessageText(int $chatId, int $messageId, string $text, array $replyMarkup = []): void;

    public function setCommandsMenu(): void;
}
