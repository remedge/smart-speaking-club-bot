<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface TelegramInterface
{
    public function getInput(): string;

    public function setWebhook(): string;

    /**
     * @param  array<int, array<int, array<string, string>>> $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, array $replyMarkup = []): void;

    public function setCommandsMenu(bool $isAdmin): void;
}
