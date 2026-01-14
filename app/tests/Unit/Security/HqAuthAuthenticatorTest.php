<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Flags\Entity\User;
use App\Flags\Repository\UserRepository;
use App\Flags\Security\HqAuthAuthenticator;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Unit tests for HqAuthAuthenticator
 * Note: Full OAuth flow testing requires integration tests due to protected parent methods.
 */
class HqAuthAuthenticatorTest extends TestCase
{
    private ClientRegistry $clientRegistry;
    private UserRepository $userRepository;
    private HqAuthAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->authenticator = new HqAuthAuthenticator(
            $this->clientRegistry,
            $this->userRepository
        );
    }

    public function testSupportsReturnsTrueForOAuthCheckRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oauth_check');

        $result = $this->authenticator->supports($request);

        $this->assertTrue($result);
    }

    public function testSupportsReturnsFalseForOtherRoutes(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'some_other_route');

        $result = $this->authenticator->supports($request);

        $this->assertFalse($result);
    }

    public function testSupportsReturnsFalseWhenNoRouteSet(): void
    {
        $request = new Request();

        $result = $this->authenticator->supports($request);

        $this->assertFalse($result);
    }

    /**
     * Test repository's loadOrCreateFromOAuth with complete OAuth data.
     */
    public function testRepositoryLoadsOrCreatesUserWithCompleteData(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('oauth_sub_12345');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'oauth_sub_12345',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $expectedUser = $this->createMockUser('oauth_sub_12345', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('loadOrCreateFromOAuth')
            ->with($userInfo)
            ->willReturn($expectedUser);

        $result = $this->userRepository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('oauth_sub_12345', $result->getSub());
        $this->assertEquals('John', $result->getFirstName());
        $this->assertEquals('Doe', $result->getLastName());
    }

    /**
     * Test repository's loadOrCreateFromOAuth with minimal OAuth data.
     */
    public function testRepositoryLoadsOrCreatesUserWithMinimalData(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('oauth_sub_67890');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'oauth_sub_67890',
            'email' => 'minimal@example.com',
        ]);

        $expectedUser = $this->createMockUser('oauth_sub_67890', null, null);

        $this->userRepository
            ->expects($this->once())
            ->method('loadOrCreateFromOAuth')
            ->with($userInfo)
            ->willReturn($expectedUser);

        $result = $this->userRepository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('oauth_sub_67890', $result->getSub());
        $this->assertNull($result->getFirstName());
        $this->assertNull($result->getLastName());
    }

    /**
     * Test repository's loadOrCreateFromOAuth with partial names.
     */
    public function testRepositoryLoadsOrCreatesUserWithPartialNames(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('oauth_sub_partial');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'oauth_sub_partial',
            'email' => 'partial@example.com',
            'first_name' => 'Jane',
        ]);

        $expectedUser = $this->createMockUser('oauth_sub_partial', 'Jane', null);

        $this->userRepository
            ->expects($this->once())
            ->method('loadOrCreateFromOAuth')
            ->with($userInfo)
            ->willReturn($expectedUser);

        $result = $this->userRepository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('oauth_sub_partial', $result->getSub());
        $this->assertEquals('Jane', $result->getFirstName());
        $this->assertNull($result->getLastName());
    }

    private function createMockUser(string $sub, ?string $firstName, ?string $lastName): User
    {
        $user = new User();
        $user->setSub($sub);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        return $user;
    }
}
