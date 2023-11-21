<?php

namespace Unit\Entity;

use App\Flags\Entity\Enum\GameType;
use App\Flags\Entity\Game;
use App\Flags\Entity\User;
use App\Flags\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameTest extends KernelTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    private function getNewInstance(): Game
    {
        $user = new User();
        return new Game($user, GameType::CAPITALS_AFRICA);
    }

    public function testCrateEntity(): void
    {
        $this->assertInstanceOf(Game::class, $this->getNewInstance());
    }

    public function testType(): void
    {
        $entity = $this->getNewInstance();
        $this->assertSame(GameType::CAPITALS_AFRICA, $entity->getType());
    }

    public function testAddQuestions(): void
    {
        $entity = $this->getNewInstance();
        $entity->addQuestion('uk');
        $this->assertCount(1, $entity->getQuestions());
        $entity->addQuestion('us');
        $this->assertCount(2, $entity->getQuestions());
        $entity->addQuestion('uk');
        $this->assertCount(2, $entity->getQuestions());
        $this->assertSame(['uk', 'us'], $entity->getQuestions());
    }
}
