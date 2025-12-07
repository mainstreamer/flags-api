<?php

namespace App\Flags\Repository;

use App\Flags\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    public function getHighScores()
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstName')
            ->addSelect('u.highScore')
            ->addSelect('u.bestTime')
            ->addSelect('u.timeTotal')
            ->addSelect('u.gamesTotal')
            ->addOrderBy('u.highScore', 'DESC')
            ->addOrderBy('u.bestTime', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getScalarResult()
        ;
    }
    
    public function getAnyUser(): User
    {
        return $this->matching(
            ($criteria = new Criteria())
                ->where(
                    $criteria
                        ->expr()
                        ->gt('id', 0))
                ->setMaxResults(1)
        )->get(0);
    }
    
    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findOneBySub($identifier) ?? $this->findOneByTelegramId($identifier);
    }

    public function loadOrCreateFromOAuth(GenericResourceOwner $userInfo): User
    {
        // Assuming your OAuth server returns "sub" as unique identifier
        $sub = $userInfo->getId();     // or ->getUid(), ->getEmail(), depending on your provider
//        $email = $userInfo->getEmail();

        // Try to find existing user
        $user = $this->findOneBy(['sub' => $sub]);

        if ($user) {
            return $user;
        }

//        $userInfoArray = $userInfo->toArray()
        // Create new user
        $user = new User();
        $user->setSub($sub);
//        $user->setEmail($userInfoArray['email'] ?? null);
//        $user->setRoles(['ROLE_USER']);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
