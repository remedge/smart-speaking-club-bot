<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Longman\TelegramBot\Telegram;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SetWebhookController
{
    public function __construct(
        private string $webhookUrl,
        private Telegram $telegram,
    )
    {
    }

    #[Route(path: '/set-webhook', methods: [Request::METHOD_POST])]
    public function __invoke(): Response
    {
        try {

            $result = $this->telegram->setWebhook($this->webhookUrl);
            if ($result->isOk()) {
                return new Response($result->getDescription());
            }
        } catch (Throwable $e) {
            return new Response($e->getMessage());
        }
    }
}