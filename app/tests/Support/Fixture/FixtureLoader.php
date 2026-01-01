<?php

declare(strict_types=1);

namespace App\Tests\Support\Fixture;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class FixtureLoader
{
    private static bool $fixturesLoaded = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContainerInterface $container,
    ) {
    }

    public function loadOnce(array $fixtureClasses = []): void
    {
        if (self::$fixturesLoaded) {
            return;
        }

        $this->load($fixtureClasses);
        self::$fixturesLoaded = true;
    }

    public function load(array $fixtureClasses = []): void
    {
        $loader = new Loader();

        foreach ($fixtureClasses as $fixtureClass) {
            $fixture = $this->container->has($fixtureClass)
                ? $this->container->get($fixtureClass)
                : new $fixtureClass();

            $loader->addFixture($fixture);
        }

        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    public static function resetLoadedState(): void
    {
        self::$fixturesLoaded = false;
    }
}
