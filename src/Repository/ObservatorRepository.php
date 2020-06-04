<?php

namespace App\Repository;

use App\Entity\Observator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Observator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Observator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Observator[]    findAll()
 * @method Observator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObservatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Observator::class);
    }
    public function addObservator($observator){

        try{
            $this->_em->persist($observator);
            $this->_em->flush();
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    public function checkObservator($user,$observator){
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.user = :user')
            ->andWhere('o.observator = :observator')
            ->setParameters([':user'=>$user->getId(),':observator'=>$observator->getId()]);
        return $qb->getQuery()->getResult();
    }
    public function countFollowers($user){
        $qb = $this->createQueryBuilder('f');
        try {
            return $qb->select('count(f.id)')
                ->where('f.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function removeObservator($observator){
        try{
            $em = $this->getEntityManager();
            $em->remove($observator);
            $em->flush();
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    // /**
    //  * @return Observator[] Returns an array of Observator objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Observator
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
