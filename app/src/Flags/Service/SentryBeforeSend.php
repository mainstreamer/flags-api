<?php

declare(strict_types=1);

namespace App\Flags\Service;

use Sentry\Event;
use Sentry\EventHint;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @psalm-suppress UnusedClass — wired via config/packages/sentry.yaml (before_send)
 */
final class SentryBeforeSend
{
    // Path prefixes owned by this API — 404s here are real bugs and should reach Sentry
    private const APP_PATH_PATTERN = '/^\/(api|flags|capitals|login|oauth)(\/|$)/i';

    // Paths that only crawlers/scanners request — never valid routes in this API
    private const SCANNER_PATTERN = '/
        \.(git|env|htaccess|htpasswd|php|asp|aspx|jsp|bak|old|sql|sh|bash|DS_Store|yml|yaml|ini|log|swp) |
        wp- | wordpress |
        phpmyadmin | adminer | pma | mysql |
        \.well-known\/(?!acme-challenge) |
        \/(actuator|console|administrator|cgi-bin|vendor\/phpunit|owa|ecp|autodiscover)\b |
        \/\.(aws|ssh|docker|vscode|idea)\b
    /xi';

    public function __invoke(Event $event, ?EventHint $hint): ?Event
    {
        try {
            $request = $event->getRequest();
            $url = \is_array($request) ? (string) ($request['url'] ?? '') : '';
            $path = '' !== $url ? (parse_url($url, PHP_URL_PATH) ?? '') : '';

            if ('' !== $path && preg_match(self::SCANNER_PATTERN, $path)) {
                return null;
            }

            $exception = $hint?->exception;
            $isRoutingMiss = $exception instanceof NotFoundHttpException || $exception instanceof MethodNotAllowedHttpException;
            if ($isRoutingMiss && ('' === $path || !preg_match(self::APP_PATH_PATTERN, $path))) {
                return null;
            }

            return $event;
        } catch (\Throwable $e) {
            // Never let a bug in this filter break the response or hide the original error.
            error_log(sprintf(
                '[SentryBeforeSend] filter failed: %s in %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));

            return $event;
        }
    }
}
