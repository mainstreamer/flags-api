<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Flags\Entity\User;
use App\Flags\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy', 'getEntityManager'])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
    }

    public function testLoadOrCreateFromOAuthReturnsExistingUser(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('existing_sub_123');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'existing_sub_123',
            'email' => 'existing@example.com',
            'first_name' => 'Existing',
            'last_name' => 'User',
        ]);

        $existingUser = new User();
        $existingUser->setSub('existing_sub_123');
        $existingUser->setFirstName('Existing');
        $existingUser->setLastName('User');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['sub' => 'existing_sub_123'])
            ->willReturn($existingUser);

        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->repository->loadOrCreateFromOAuth($userInfo);

        $this->assertSame($existingUser, $result);
        $this->assertEquals('existing_sub_123', $result->getSub());
    }

    public function testLoadOrCreateFromOAuthCreatesNewUserWithCompleteData(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('new_sub_456');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'new_sub_456',
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['sub' => 'new_sub_456'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User
                    && 'new_sub_456' === $user->getSub()
                    && 'New' === $user->getFirstName()
                    && 'User' === $user->getLastName();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('new_sub_456', $result->getSub());
        $this->assertEquals('New', $result->getFirstName());
        $this->assertEquals('User', $result->getLastName());
    }

    public function testLoadOrCreateFromOAuthCreatesUserWithMinimalData(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('minimal_sub_789');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'minimal_sub_789',
            'email' => 'minimal@example.com',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['sub' => 'minimal_sub_789'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User
                    && 'minimal_sub_789' === $user->getSub()
                    && null === $user->getFirstName()
                    && null === $user->getLastName();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('minimal_sub_789', $result->getSub());
        $this->assertNull($result->getFirstName());
        $this->assertNull($result->getLastName());
    }

    public function testLoadOrCreateFromOAuthCreatesUserWithPartialNames(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('partial_sub_999');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'partial_sub_999',
            'email' => 'partial@example.com',
            'first_name' => 'OnlyFirst',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['sub' => 'partial_sub_999'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User
                    && 'partial_sub_999' === $user->getSub()
                    && 'OnlyFirst' === $user->getFirstName()
                    && null === $user->getLastName();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('partial_sub_999', $result->getSub());
        $this->assertEquals('OnlyFirst', $result->getFirstName());
        $this->assertNull($result->getLastName());
    }

    public function testLoadOrCreateFromOAuthHandlesEmptyStringNames(): void
    {
        $userInfo = $this->createMock(GenericResourceOwner::class);
        $userInfo->method('getId')->willReturn('empty_names_sub');
        $userInfo->method('toArray')->willReturn([
            'sub' => 'empty_names_sub',
            'email' => 'empty@example.com',
            'first_name' => '',
            'last_name' => '',
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['sub' => 'empty_names_sub'])
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) {
                return $user instanceof User
                    && 'empty_names_sub' === $user->getSub()
                    && '' === $user->getFirstName()
                    && '' === $user->getLastName();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->repository->loadOrCreateFromOAuth($userInfo);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('empty_names_sub', $result->getSub());
    }
}
