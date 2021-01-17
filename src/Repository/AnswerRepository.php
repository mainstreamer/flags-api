<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Flag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Flag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Flag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Flag[]    findAll()
 * @method Flag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }
    
    public function findIncorrectGuesses(string $userId): array
    {
//        SELECT COUNT(answer.flag_code) as incorrect, answer.flag_code FROM answer WHERE answer.user_id = 6 AND answer.correct = 0 
//GROUP BY answer.flag_code ORDER BY incorrect DESC;
        
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.flagCode) as times')
            ->addSelect('a.flagCode')
            ->where('a.user = :userId')
            ->andWhere('a.correct = :false')
            ->groupBy('a.flagCode')
            ->orderBy('times', 'DESC')
            ->setParameter('userId', $userId)
            ->setParameter('false', 0)
            ->getQuery()
            ->getArrayResult()
        ;
    }
    
    public function findCorrectGuesses(string $userId): array
    {
        //        SELECT COUNT(answer.flag_code) as incorrect, answer.flag_code FROM answer WHERE answer.user_id = 6 AND answer.correct = 0 
        //GROUP BY answer.flag_code ORDER BY incorrect DESC;
        
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.flagCode) as times')
            ->addSelect('a.flagCode')
            ->where('a.user = :userId')
            ->andWhere('a.correct = :true')
            ->groupBy('a.flagCode')
            ->orderBy('times', 'DESC')
            ->setParameter('userId', $userId)
            ->setParameter('true', 1)
            ->getQuery()
            ->getArrayResult()
        ;
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
