<?php

namespace App\Flags\Repository;

use App\Flags\Entity\CapitalsStat;
use App\Flags\Entity\Flag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Flag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Flag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Flag[]    findAll()
 * @method Flag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CapitalsStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CapitalsStat::class);
    }


//    public function getHighScores(): iterable
//    {
//        return $this->createQueryBuilder('s')
//            ->select('s.firstName')
//            ->addSelect('s.score')
//            ->addSelect('s.sessionTimer')
//            ->addSelect('s.timeTotal')
//            ->addOrderBy('s.score', 'DESC')
//            ->addOrderBy('s.sessionTimer', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getScalarResult()
//        ;
//
//    }

    public function getHighScores(): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('u.firstName, u.lastName, u.id, t.score, MIN(t.sessionTimer) as sessionTimer')
            ->innerJoin('t.user', 'u')
            ->where(
                $qb->expr()->in(
                    't.score',
                    $this->createQueryBuilder('subT')
                        ->select('MAX(subT.score)')
                        ->where('subT.user = t.user')
                        ->getDQL()
                )
            )
            ->groupBy('t.score, u.id')
            ->orderBy('t.score', 'DESC')
            ->addOrderBy('sessionTimer', 'ASC')
            ->setMaxResults(10);
        ;

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $results;
    }

    // /**
    //  * @return Flag[] Returns an array of Flag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Flag
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
