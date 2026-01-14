<?php

namespace App\Flags\Security;

use App\Flags\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HqAuthAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        //        private RouterInterface $router,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'oauth_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        //         DEBUG: See what session ID we are using
        //        dump("Session ID: " . $request->getSession()->getId());

        //         DEBUG: See what state is in the URL vs the Session
        //        dump("URL State: " . $request->query->get('state'));
        //        dd("Session State: " . $request->getSession()->get('oauth2state'));

        // Here a callback from oauth server hits with "code" and "state" url params
        // after user has successfully authenticated with oauth and granted access
        $client = $this->clientRegistry->getClient('flags_app');

        // THIS is a magical moment where all happens in parent (OAuth2Authenticator)
        // from global state via traits data is collected (OMG!)
        // It pulls the "expected" state from the Symfony Session (which is stored in your Redis)
        // The Comparison: If they don't match, it throws the InvalidStateException
        $accessToken = $this->fetchAccessToken($client);
        // A. The Redis Connection (DSN) Changed
        // If your .env change updated REDIS_DSN, your "Trip 1" saved the state in one Redis instance (or database index), but your "Trip 2" is looking for it in another. If Redis is empty or the key is missing, Symfony assumes a CSRF attack and says "Invalid State."
        // B. The APP_SECRET Changed
        //
        // Symfony uses the APP_SECRET (from your backend .env) to sign the session cookie.
        //
        //    If you changed the APP_SECRET, the session cookie in your browser becomes invalid.
        //
        //    When you come back from the OAuth server, Symfony can't decrypt your session cookie, creates a new empty session, and finds no state inside it.
        //
        // C. stateless: true vs false
        //
        // Notice that your api firewall is stateless: true, but your oauth firewall is stateless: false. If your React app tries to start the login via an /api/... route instead of the /login route, the session will never be saved, and the state check will always fail.
        //        // Optional: parse & verify JWT locally
        //        $jwt = $accessToken->getToken();
        //        $token = $this->jwtParser->parse($jwt); // e.g., lcobucci/jwt
        //        if (!$token->verify($signer, $publicKey) || $token->isExpired(new \DateTimeImmutable())) {
        //            throw new AuthenticationException('Invalid or expired token');
        //        }
        //         Store the access token in the request for later use

        $request->attributes->set('oauth_access_token', $accessToken->getToken());
        $request->attributes->set('oauth_refresh_token', $accessToken->getRefreshToken());
        $request->attributes->set('oauth_expires_in', $accessToken->getExpires());

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                $userInfo = $client->fetchUserFromToken($accessToken);

                return $this->userRepository->loadOrCreateFromOAuth($userInfo);
                // Here you'd load or create your user based on the OAuth data
                // For example, using the 'sub' claim as the user identifier
                //                return $this->loadOrCreateUser($userInfo);
            })
        );
    }

    //    public function onAuthenticationSuccess(R
    // equest $request,
    // TokenInterface $token,
    // string $firewallName
    // ): ?Response
    //    {
    //        return new JsonResponse([$token->getUser()->getUserIdentifier(), implode($token->getUser()->getRoles())]);
    // //        return new RedirectResponse($this->router->generate('app_dashboard'));
    //    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get the JWT access token
        $accessToken = $request->attributes->get('oauth_access_token');
        $refreshToken = $request->attributes->get('oauth_refresh_token');
        $expiresIn = $request->attributes->get('oauth_expires_in');

        return new Response("<!DOCTYPE html><script>
            window.opener.postMessage({
                type: 'oauth_success',
                access_token: '$accessToken',
                refresh_token: '$refreshToken',
                expires_in: '$expiresIn',
            }, '*');
            window.close();
        </script>");
    }

    #[\Override]
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        // DEBUG: Log the actual error
        error_log('OAuth authentication failed: ' . $exception->getMessage());
        error_log(
            sprintf(
                'Previous exception: %s',
                $exception->getPrevious()
                    ? $exception->getPrevious()->getMessage()
                    : 'none'
            )
        );

        // Temporarily return error instead of redirect loop
        return new JsonResponse([
            'error' => 'authentication_failed',
            'message' => $exception->getMessage(),
            'previous' => $exception->getPrevious() ? $exception->getPrevious()->getMessage() : null,
        ], 401);
    }

    //    private function loadOrCreateUser($userInfo)
    //    {
    //        // Implement your user loading/creation logic
    //        // You might want to inject UserRepository here
    //    }
}
