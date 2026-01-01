<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\ApiClient;
use App\Tests\Support\Factory\FlagFactory;
use App\Tests\Support\Factory\GameFactory;
use App\Tests\Support\Factory\UserFactory;
use App\Tests\Support\Fixture\FixtureLoader;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected ApiClient $api;
    protected UserFactory $users;
    protected GameFactory $games;
    protected FlagFactory $flags;
    protected EntityManagerInterface $em;

    private Connection $connection;

    /**
     * Override in subclass to load fixtures once per test class.
     * Example: return [UserFixture::class, GameFixture::class];
     */
    protected function fixtures(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $browser = static::createClient();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        $this->users = new UserFactory($this->em);
        $this->games = new GameFactory($this->em, $this->users);
        $this->flags = new FlagFactory($this->em);
        $this->api = new ApiClient($browser, $this->users);

        $fixtures = $this->fixtures();
        if (!empty($fixtures)) {
            $loader = new FixtureLoader($this->em, $container);
            $loader->loadOnce($fixtures);
        }

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $this->em->clear();

        parent::tearDown();
    }

    protected function refreshEntity(object $entity): object
    {
        $this->em->refresh($entity);

        return $entity;
    }
}
