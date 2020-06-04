<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }
    public function countCommentsByUser($user){
        $qb = $this->createQueryBuilder('c');
        try {
            return $qb->select('count(c.id)')
                ->where('c.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }
    public function updateComment(Comment $comment){
        try{
            $em = $this->getEntityManager();
            $em->persist($comment);
            $em->flush($comment);
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }

    /**
     * @param int $textId
     * @param int|null $commentId
     * @return array
     */
    public function getComments(int $textId = null, int $commentId = null): array {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.softDelete IS NULL');
        if($commentId){
            $qb->andWhere('c.parentComment = :commentId')
                ->setParameter('commentId',$commentId);
        } elseif ($textId) {
            $qb->andWhere('c.text = :textId')
                ->setParameter('textId', $textId);
        }
        return $qb->getQuery()
                ->getResult();
    }
    // /**
    //  * @return Comment[] Returns an array of Comment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Comment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
