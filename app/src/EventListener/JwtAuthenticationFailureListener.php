<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTFailureEventInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtAuthenticationFailureListener implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_invalid' => 'onJwtInvalid',
            'lexik_jwt_authentication.on_jwt_not_found' => 'onJwtNotFound',
            'lexik_jwt_authentication.on_jwt_expired' => 'onJwtExpired',
        ];
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $exception = $event->getException();
        $this->logger->error('JWT Invalid: ' . $exception->getMessage(), [
            'previous' => $exception->getPrevious()?->getMessage(),
        ]);
    }

    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $exception = $event->getException();
        $this->logger->warning('JWT Not Found: ' . $exception->getMessage());
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $exception = $event->getException();
        $this->logger->warning('JWT Expired: ' . $exception->getMessage());
    }
}
