<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Domain\TelegramInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SetWebhookController
{
    public function __construct(
        private TelegramInterface $telegram,
    ) {
    }

    #[Route(path: '/set-webhook', methods: [Request::METHOD_POST])]
    public function __invoke(): Response
    {
        try {
            return new Response($this->telegram->setWebhook());
        } catch (Throwable $e) {
            return new Response($e->getMessage());
        }
    }
}
