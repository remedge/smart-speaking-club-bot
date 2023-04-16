<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface TelegramInterface
{
    public function getInput(): string;

    public function setWebhook(): string;

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void;
}