<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Image;
use App\Entity\Observator;
use App\Entity\Setting;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getLastEvents(User $user, int $limit = 15){
        $qb = $this->createQueryBuilder('e');
        return $qb->select(['distinct(e.id) as event_id','c.id','IDENTITY(c.parentComment) as parent_comment_id','i.path','IDENTITY(e.text) as text_id','t.title','t.slug','a.id as author','a.nick as author_nick','o.id as receiver','o.nick as receiver_nick','o.email as email','e.type as event_type','e.isRead as is_read'])
                    ->leftJoin('e.text','t','with','e.text = t.id')
                    ->leftJoin(Observator::class,'ob','WITH','e.user = ob.user')
                    ->leftJoin(Comment::class, 'c','WITH','c.id = e.comment')
                    ->innerJoin('t.user','a','WITH','t.user = a.id')
                    ->leftJoin('e.user','o','WITH','e.user = o.id')
                    ->leftJoin(Image::class,'i','WITH','(i.user = a.id AND (e.type != :reply AND e.type != :newComm )) OR c.user = i.user')
                    ->where('ob.observator = :userId OR (e.user IS NULL OR c.user = e.user)')
                    ->orWhere('e.user != :userId')
                    ->andWhere('i.type = :type OR i.path IS NULL')
                    ->setParameters(['userId'=> $user->getId(),'type'=>Image::TYPE_NAVBAR_THUMB,'newComm'=>Event::NEW_COMMENT_FOR_USER, 'reply'=>Event::REPLY_COMMENT])
                    ->setMaxResults($limit)
                    ->orderBy('e.id','DESC')
                    ->getQuery()
                    ->getResult();
    }
    public function countUnreadEvents(User $user){
        $qb = $this->createQueryBuilder('e');
        return $qb->select('count(e.id)')
            ->leftJoin('e.user','u','WITH','e.user = u.id')
            ->leftJoin(Observator::class,'o','WITH','u.id = o.user')
            ->where('e.isRead = :f')
            ->andWhere('o.observator = :id')
            ->setParameters(['id'=>$user->getId(),'f'=>false])
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function getEventsToSetRead(User $user){
        $qb = $this->createQueryBuilder('e');
        return $qb->leftJoin(Observator::class,'o','WITH','e.user = o.user')
            ->where('e.isRead = :t')
            ->andWhere('o.observator = :id')
            ->setParameters(['id'=>$user->getId(),'t'=>false])
            ->getQuery()
            ->getResult();
    }
    public function getAllEvents(User $user, int $skip = 0){
        $qb = $this->createQueryBuilder('e');
        return $qb->select(['distinct(e.id) as event_id','c.id','IDENTITY(c.parentComment) as parent_comment_id','i.path','IDENTITY(e.text) as text_id','t.title','t.slug','a.id as author','a.nick as author_nick','o.id as receiver','o.nick as receiver_nick','o.email as email','e.type as event_type','e.isRead as is_read'])
            ->leftJoin('e.text','t','with','e.text = t.id')
            ->leftJoin(Observator::class,'ob','WITH','e.user = ob.user')
            ->leftJoin(Comment::class, 'c','WITH','c.id = e.comment')
            ->innerJoin('t.user','a','WITH','t.user = a.id')
            ->leftJoin('e.user','o','WITH','e.user = o.id')
            ->leftJoin(Image::class,'i','WITH','(i.user = a.id AND (e.type != :reply AND e.type != :newComm )) OR c.user = i.user')
            ->where('ob.observator = :userId OR (e.user IS NULL OR c.user = e.user)')
            ->orWhere('e.user != :userId')
            ->andWhere('i.type = :type OR i.path IS NULL')
            ->setParameters(['userId'=> $user->getId(),'type'=>Image::TYPE_NAVBAR_THUMB,'newComm'=>Event::NEW_COMMENT_FOR_USER, 'reply'=>Event::REPLY_COMMENT])
            ->setMaxResults(Event::SKIP_EVENTS)
            ->orderBy('e.id','DESC')
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return Event[] Returns an array of Event objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
