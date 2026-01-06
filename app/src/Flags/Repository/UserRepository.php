<?php

namespace App\Flags\Repository;

use App\Flags\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User[]    findAll()
 * @method User|null findOneBy(array $criteria)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getHighScores(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstName')
            ->addSelect('u.highScore')
            ->addSelect('u.bestTime')
            ->addSelect('u.timeTotal')
            ->addSelect('u.gamesTotal')
            ->addOrderBy('u.highScore', 'DESC')
            ->addOrderBy('u.bestTime', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getScalarResult()
        ;
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findOneBy(['sub' => $identifier]) ?? $this->find($identifier);
    }

    public function loadOrCreateFromOAuth(GenericResourceOwner $userInfo): User
    {
        // OAuth server returns "sub" as unique identifier (required)
        $sub = $userInfo->getId();

        // Try to find existing user by sub
        $user = $this->findOneBy(['sub' => $sub]);

        if ($user) {
            return $user;
        }

        // Create new user with minimal required data
        $userInfoArray = $userInfo->toArray();
        $user = new User();
        $user->setSub($sub);

        // Optional fields - names can be null
        $user->setFirstName($userInfoArray['first_name'] ?? null);
        $user->setLastName($userInfoArray['last_name'] ?? null);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
