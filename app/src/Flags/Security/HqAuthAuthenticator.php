<?php

// namespace App\Flags\Security;
//
// use App\Flags\Repository\UserRepository;
// use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
// use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\RedirectResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\RouterInterface;
// use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// use Symfony\Component\Security\Core\Exception\AuthenticationException;
// use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
// use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
// use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
//
// class HqAuthAuthenticator extends OAuth2Authenticator
// {
//    public function __construct(
//        private ClientRegistry  $clientRegistry,
//        private RouterInterface $router,
//        private UserRepository  $userRepository,
//    )
//    {
//    }
//
//    public function supports(Request $request): ?bool
//    {
//        return $request->attributes->get('_route') === 'oauth_check';
//    }
//
//    public function authenticate(Request $request): Passport
//    {
//        $client = $this->clientRegistry->getClient('flags_app');
//        $accessToken = $this->fetchAccessToken($client);
//
//        // Store the access token in the request for later use
//        $request->attributes->set('oauth_access_token', $accessToken->getToken());
//        $request->attributes->set('oauth_refresh_token', $accessToken->getRefreshToken());
//        $request->attributes->set('oauth_expires_in', $accessToken->getExpires());
//
//        return new SelfValidatingPassport(
//            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
//                $userInfo = $client->fetchUserFromToken($accessToken);
//                return $this->userRepository->loadOrCreateFromOAuth($userInfo);
//            })
//        );
//    }
//
//    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
//    {
//        // Get the JWT access token
//        $accessToken = $request->attributes->get('oauth_access_token');
//        $refreshToken = $request->attributes->get('oauth_refresh_token');
//        $expiresIn = $request->attributes->get('oauth_expires_in');
//
//        // Return JSON with the tokens for the frontend
//        return new JsonResponse([
//            'success' => true,
//            'access_token' => $accessToken,
//            'refresh_token' => $refreshToken,
//            'expires_in' => $expiresIn,
//            'token_type' => 'Bearer',
//            'user' => [
// //                'email' => $token->getUser()->getEmail(),
//                'roles' => $token->getUser()->getRoles(),
//            ]
//        ]);
//    }
//
//    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
//    {
//        return new JsonResponse([
//            'success' => false,
//            'error' => $exception->getMessage()
//        ], Response::HTTP_UNAUTHORIZED);
//    }
// }

namespace App\Flags\Security;

use App\Flags\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lcobucci\JWT\Parser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        private ClientRegistry $clientRegistry,
        private RouterInterface $router,
        private UserRepository $userRepository,
        //        private Parser $jwtParser,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'oauth_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('flags_app');
        $accessToken = $this->fetchAccessToken($client);

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

    //    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    //    {
    //        return new JsonResponse([$token->getUser()->getUserIdentifier(), implode($token->getUser()->getRoles())]);
    // //        return new RedirectResponse($this->router->generate('app_dashboard'));
    //    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        //        dd($request->toArray());
        // Get the JWT access token
        $accessToken = $request->attributes->get('oauth_access_token');
        $refreshToken = $request->attributes->get('oauth_refresh_token');
        $expiresIn = $request->attributes->get('oauth_expires_in');

        return new Response("<!DOCTYPE html><script>
            window.opener.postMessage({
                type: 'oauth_success',
                access_token: '$accessToken',
                refresh_token: '$refreshToken'
            }, '*');
            window.close();
        </script>");

        // Return JSON with the tokens for the frontend
        //        return new JsonResponse([
        //            'success' => true,
        //            'access_token' => $accessToken,
        //            'refresh_token' => $refreshToken,
        //            'expires_in' => $expiresIn,
        //            'token_type' => 'Bearer',
        //            'user' => [
        // //                'email' => $token->getUser()->getEmail(),
        //                'roles' => $token->getUser()->getRoles(),
        //            ]
        //        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // DEBUG: Log the actual error
        error_log('OAuth authentication failed: '.$exception->getMessage());
        error_log('Previous exception: '.($exception->getPrevious() ? $exception->getPrevious()->getMessage() : 'none'));

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
