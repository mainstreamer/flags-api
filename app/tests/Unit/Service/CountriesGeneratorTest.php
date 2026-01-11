<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Flags\Entity\User;
use App\Flags\Repository\UserRepository;
use App\Flags\Security\HqAuthAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\RouterInterface;

/**
 * Unit tests for HqAuthAuthenticator
 * Note: Full OAuth flow testing requires integration tests due to protected parent methods.
 */
class CountriesGeneratorTest extends TestCase
{
//
//    protected function setUp(): void
//    {
//        $this->clientRegistry = $this->createMock(ClientRegistry::class);
//        $this->router = $this->createMock(RouterInterface::class);
//        $this->userRepository = $this->createMock(UserRepository::class);
//
//        $this->authenticator = new HqAuthAuthenticator(
//            $this->clientRegistry,
//            $this->router,
//            $this->userRepository
//        );
//    }

    public function testCountryIsFetched(): void
    {
        $this->assertTrue(Countries::exists('AC'));
    }
}
