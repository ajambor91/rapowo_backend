<?php

namespace App\Repository;

use App\Entity\DeletedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DeletedUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeletedUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeletedUser[]    findAll()
 * @method DeletedUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeletedUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeletedUser::class);
    }

    // /**
    //  * @return DeletedUser[] Returns an array of DeletedUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DeletedUser
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
