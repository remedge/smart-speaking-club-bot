<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ExceptionListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HandlerFailedException && ($previousException = $exception->getPrevious()) !== null) {
            $exception = $previousException;
        }

        $response = new JsonResponse(
            data: [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
        );

        $this->logger->critical('exception', [$exception->getMessage()]);

        $event->setResponse($response);
    }
}
