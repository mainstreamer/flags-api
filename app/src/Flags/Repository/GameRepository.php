<?php

namespace App\Flags\Repository;

use App\Flags\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /** @psalm-suppress PossiblyUnusedParam */
    public function getById(int $id): Game
    {
        return $this->findOneBy(['id' => $id])
            ?? throw EntityNotFoundException::fromClassNameAndIdentifier(className: Game::class, id: [$id]);
    }
}
