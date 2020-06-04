<?php

namespace App\Repository;

use App\Entity\NotedText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method NotedText|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotedText|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotedText[]    findAll()
 * @method NotedText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotedTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotedText::class);
    }
    public function getNotedTextByUser($user, $text, $type){
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.user = :user')
            ->andWhere('t.text = :text')
            ->andWhere('t.type = :type')
            ->setParameters([':user'=>$user->getId(),':text'=>$text->getId(),':type'=>$type]);
        return $qb->getQuery()->getResult();
    }
    public function getMainNotedTextByUser($user, $text){
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.user = :user')
            ->andWhere('t.text = :text')
            ->setParameters([':user'=>$user->getId(),':text'=>$text->getId()]);
        return $qb->getQuery()->getResult();
    }
    public function addNote($noted){
        try{
            $em = $this->getEntityManager();
            $em->persist($noted);
            $em->flush();
            return true;

        }catch (\Exception $exception){
            return $exception;
        }
    }
    public function removeNote($note){
        try{
            $em = $this->getEntityManager();
            $em->remove($note);
            $em->flush();
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    // /**
    //  * @return NotedText[] Returns an array of NotedText objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NotedText
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
