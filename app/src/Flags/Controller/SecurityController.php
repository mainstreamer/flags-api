<?php

namespace App\Flags\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('/login', name: 'app_login')]
    public function login(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('flags_app')
            ->redirect(['openid', 'profile', 'email'], []);
    }

    #[Route('/debug/headers', name: 'debug_headers')]
    public function debugHeaders(Request $request): JsonResponse
    {
        return new JsonResponse([
            'scheme' => $request->getScheme(),
            'isSecure' => $request->isSecure(),
            'host' => $request->getHost(),
            'clientIp' => $request->getClientIp(),
            'X-Forwarded-Proto' => $request->headers->get('X-Forwarded-Proto'),
            'X-Forwarded-For' => $request->headers->get('X-Forwarded-For'),
            'X-Forwarded-Host' => $request->headers->get('X-Forwarded-Host'),
            'trustedProxies' => Request::getTrustedProxies(),
            'server_HTTPS' => $_SERVER['HTTPS'] ?? 'not set',
        ]);
    }

    #[Route('/oauth/check', name: 'oauth_check')]
    public function check()
    {
        // This route is handled by the authenticator
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout()
    {
        throw new \LogicException('This should never be reached');
    }

    #[Route('/api/refresh', name: 'api_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        # TODO Check if it works at all

        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return new JsonResponse(['error' => 'No refresh token provided'], 400);
        }

        try {
            $response = $this->httpClient->request('POST', $_ENV['OAUTH_SERVER_URL'] . '/oauth2/token', [
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $_ENV['OAUTH_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH_CLIENT_SECRET'],
                ]
            ]);

            $tokens = $response->toArray();

            return new JsonResponse([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $refreshToken,
                'expires_in' => $tokens['expires_in'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to refresh token'], 401);
        }
    }
}