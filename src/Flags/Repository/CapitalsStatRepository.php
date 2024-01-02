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


    public function getHighScores3(string $gameType): array
    {
        $qb = $this->createQueryBuilder('t');

// Subquery to find rows with the maximum score for each user and gameType
        $subQuery = $this->createQueryBuilder('subT')
            ->select('MAX(subT.score) as maxScore, subT.user, subT.gameType')
            ->groupBy('subT.user, subT.gameType');

        $gameTypeParameter = $gameType;

        $qb->select('u.firstName, u.lastName, u.id, t.score, t.gameType, t.sessionTimer')
            ->innerJoin('t.user', 'u')
            ->innerJoin(
                '(' . $subQuery->getDQL() . ')',
                'maxScores',
                'WITH',
                't.user = maxScores.user AND t.gameType = maxScores.gameType AND t.score = maxScores.maxScore'
            )
            ->where('t.gameType = :gameType')
            ->setParameter('gameType', $gameTypeParameter)
            ->orderBy('t.score', 'DESC')
            ->addOrderBy('t.sessionTimer', 'ASC')
            ->setMaxResults(10);

        $query = $qb->getQuery();
//
////        dump($gameType);
//        $qb = $this->createQueryBuilder('t');
//        $qb->select('u.firstName, u.lastName, u.id, t.score, t.gameType, MIN(t.sessionTimer) as sessionTimer')
//            ->innerJoin('t.user', 'u')
//            ->where(
//                $qb->expr()->eq(
//                    't.score',
//                    '(' .$this->createQueryBuilder('subT')
//                        ->select('MAX(subT.score)')
//                        ->where('subT.user = t.user')
//                        ->where('subT.gameType = :gameType')
//                        ->getDQL() . ')'
//                )
//            )
//            ->setParameter('gameType', $gameType)
//
//            ->groupBy('t.score, u.id, t.gameType')
//            ->orderBy('t.score', 'DESC')
//            ->addOrderBy('sessionTimer', 'ASC')
//            ->setMaxResults(10);
//        ;
//
//
//        $query = $qb->getQuery();
////        $query->setParameter('gameType', $gameType);
        $results = $query->getResult();

        return $results;
    }

    public function getHighScores2(string $gameType): array
    {
//        dump($gameType);
        $qb = $this->createQueryBuilder('t');
//        $qb->select('u.firstName, u.lastName, u.id, t.score, t.gameType, MIN(t.sessionTimer) as sessionTimer')
        $qb->select('u.firstName, u.lastName, u.id, t.score as score, t.gameType, MIN(t.sessionTimer) as sessionTimer')
//            ->innerJoin('t.user', 'u', 'WITH', 't.gameType = :gameType' )
            ->innerJoin('t.user', 'u')
//            ->innerJoin(
//                    '(' . $this->createQueryBuilder('subT')
//                        ->select('MAX(subT.score) as maxScore')
////                        ->where('subT.user = t.user')
//                        ->getDQL() . ')'
////                        ->where('subT.sessionTimer = sessionTimer')
//
//                ,
//                'maxScore',
//                'WITH',
//                'maxScore.gameType = :gameType'
//            )
////
            ->where(
                $qb->expr()->in(
                    't.score',
                    $this->createQueryBuilder('subT')
                        ->select('MAX(subT.score) as maxScore')
                        ->where('subT.user = t.user')
                        ->andWhere('subT.gameType = :gameType')
                        ->groupBy('subT.user, subT.gameType')
//                        ->where('subT.sessionTimer = sessionTimer')
                        ->getDQL()
                )
            )
            // TODO JOIN WITH SUBQUEry
//            ->andWhere(
//                $qb->expr()->in(
//                    't.sessionTimer',
//                    $this->createQueryBuilder('subT2')
//                        ->select('MIN(subT2.sessionTimer)')
////                        ->innerJoin('subT2.user', 'u2')
//                        ->where('subT2.user = t.user')
//                        ->andWhere('subT2.gameType = :gameType')
//                        ->groupBy('subT2.user, subT2.gameType')
////                        ->andWhere('subT2.score = :scoreMax')
////                        ->where('subT.sessionTimer = sessionTimer')
//                        ->getDQL()
//                )
//            )
//            ->andWhere('t.gameType = :gameType')
            ->setParameter('gameType', $gameType)
//            ->setParameter('scoreMax',  $qb->expr()->eq(
//                't.score',
//                '(' .$this->createQueryBuilder('subT')
//                    ->select('MAX(subT.score)')
//                    ->where('subT.user = t.user')
//                    ->andWhere('subT.gameType = :gameType')
////                        ->where('subT.sessionTimer = sessionTimer')
//                    ->getDQL() . ')'
//            ))

            ->groupBy('t.score, u.id, t.sessionTimer, t.gameType')
            ->orderBy('t.score', 'DESC')
            ->addOrderBy('sessionTimer', 'ASC')
            ->setMaxResults(10)
        ;


        $query = $qb->getQuery();
//        $query->setParameter('gameType', $gameType);
        $results = $query->getResult();

        return $results;
    }

    public function getHighScores(string $gameType): array
    {

        $em = $this->getEntityManager();

        $sql = '
    WITH max_scores AS (
        SELECT
            t.user_id,
            t.game_type,
            MAX(t.score) AS max_score
        FROM
            capitals_stat t
        WHERE
            t.game_type = :gameType
        GROUP BY
            t.user_id,
            t.game_type
    )
    SELECT
        u.first_name as firstName,
        u.last_name as lastName,
        u.id AS userId,
        t.score as score,
        t.game_type as gameType,
        MIN(t.session_timer) AS sessionTimer
    FROM
        capitals_stat t
    JOIN
        max_scores ms ON t.user_id = ms.user_id AND t.game_type = ms.game_type AND t.score = ms.max_score
    JOIN
        user u ON t.user_id = u.id
    WHERE
        t.game_type = :gameType
    GROUP BY
        u.id,
        t.game_type,
        t.score
    ORDER BY
        t.score DESC,
        sessionTimer ASC
    LIMIT
        10
';

        $connection = $em->getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue('gameType', $gameType);
        $result = $statement->executeQuery();
return $result->fetchAllAssociative();
//        $result = $statement->fetchAll();
//        return $result;
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
