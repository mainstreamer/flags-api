<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonExceptionListener implements EventSubscriberInterface
{
    public function __construct(private string $environment)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $data = [
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $statusCode,
        ];

        if ('dev' === $this->environment) {
            $data['trace'] = $exception->getTraceAsString();
        }

        $response = new JsonResponse($data, $statusCode);
        $event->setResponse($response);
    }
}
