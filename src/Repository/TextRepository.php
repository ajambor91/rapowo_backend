<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\Setting;
use App\Entity\Text;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Text|null find($id, $lockMode = null, $lockVersion = null)
 * @method Text|null findOneBy(array $criteria, array $orderBy = null)
 * @method Text[]    findAll()
 * @method Text[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Text::class);
    }
    public function addText($text){
        try{
            $em = $this->getEntityManager();
            $em->persist($text);
            $em->flush();
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    public function updateText($text){
        try{
            $em = $this->getEntityManager();
            $em->flush($text);
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    public function removeText($text){
        try {
            $em = $this->getEntityManager();
            $em->remove($text);
            $em->flush();
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    public function getTexts(int $skip,int $limit,bool $isAccept = true){
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin(User::class,'u','with','u.id = t.user')
            ->setFirstResult($skip)
            ->setMaxResults($limit)
            ->where('u.softdeleta IS NULL')
            ->andWhere('t.softDelete IS NULL')
            ->andWhere('t.isAccepted = :accept')
            ->andWhere('t.draft = false')
            ->setParameter(':accept',$isAccept)
            ->orderBy('t.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }
    public function getUserTexts($user,$skip, $limit){
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.user = :user')
            ->where('t.softDelete IS NULL')
            ->andWhere('t.isAccepted = true')
            ->setFirstResult($skip)
            ->setMaxResults($limit)
            ->setParameter(':user', $user->getId())
            ->orderBy('t.createdAt','DESC');
        return $qb->getQuery()->getResult();
    }
    public function countTexts($accept = true){
        $qb = $this->createQueryBuilder('t');
        $qb->select('count(t.id)')
            ->where('t.softDelete IS NULL')
            ->andWhere('t.isAccepted = :accept')
            ->andWhere('t.draft = false')
            ->setParameter('accept', $accept);
        return $qb->getQuery()->getSingleScalarResult();
    }
    public function countUserText($user){
        $qb = $this->createQueryBuilder('t');
        try {
            return $qb->select('count(t.id)')
                ->where('t.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
        //TODO przyjrzeć się temu
    }
    public function getUserNotes($user){
        $qb = $this->createQueryBuilder('t');
        try {
            return $qb->select('sum(t.note)')
                ->where('t.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function countCommentForUser($user){
        $qb = $this->createQueryBuilder('t');
        try {
            return $qb->select('count(c.id)')
                ->leftJoin(Comment::class, 'c', 'with', 'c.text = t.id')
                ->where('t.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function getNewTexts(){
        $qb = $this->createQueryBuilder('t');
        return $qb->select(['t.title','t.content','t.id as text_id','i.path','u.nick','t.slug'])
                    ->innerJoin('t.user','u','u.id = t.user')
                    ->leftJoin(Image::class,'i','WITH','i.user = t.user')
                    ->where('t.createdAt < :now')
                    ->orderBy('t.createdAt', 'DESC')
                    ->setParameter('now', (new \DateTime())->modify('- 2 days'))
                    ->setMaxResults(Text::MAX_TEXTS)
                    ->getQuery()
                    ->getResult();
        }

    public function getMostCommented(){
        $qb = $this->createQueryBuilder('t');
        return $qb->select(['t.title','t.content','t.id','u.nick','t.slug'])
                    ->distinct('t.id')
                    ->addSelect('(SELECT count(c.id) as comment_sum FROM App\Entity\Text te  WHERE te.id = c.text)')
                    ->addSelect('(SELECT distinct(i.path) from App\Entity\Image i where t.user = i.user and (i.type = 2 or i.path IS NULL) ) as path')
                    ->innerJoin(Comment::class,'c','WITH','t.id = c.text')
                    ->innerJoin('c.parentComment','cp','WITH','cp.parentComment = c.id')
                    ->innerJoin('t.user','u','u.id = t.user')
                    ->groupBy('t.id')
                    ->where('c.createdAt < :now')
                    ->setParameter('now',(new \DateTime())->modify('-2 days'))
                    ->getQuery()
                    ->getResult();
    }
    public function getPopularTexts(){
        $qb = $this->createQueryBuilder('t');
        return $qb->select(['t.title','t.content','t.id','u.nick','i.path','t.slug'])
                    ->innerJoin('t.user','u','t.user = u.id')
                    ->innerJoin(Image::class, 'i','t.user = i.user')
                    ->where('t.note > :limit')
                    ->andWhere('t.updatedAt < :now')
                    ->andWhere('i.type = :type OR i.path IS NULL')
                    ->setParameters(['now'=>(new \DateTime())->modify('-2 days'),'limit'=>Text::POPULAR_LIMIT, 'type'=> Image::TYPE_NAVBAR_THUMB])
                    ->orderBy('t.note', 'DESC')
                    ->setMaxResults(Text::MAX_TEXTS)
                    ->getQuery()
                    ->getResult();
    }
    public function getSortedTexts(string $sort, int $skip, int $maxResult, string $desc): array {
        switch ($sort){
            case 'id':
                $sort = 't.id';
                break;
            case 'title':
                $sort = 't.title';
                break;
            case 'author':
                $sort = 'u.nick';
                break;
            case 'createdAt':
            default:
                $sort = 't.createdAt';
                break;
        }
        $qb = $this->createQueryBuilder('t');
        return $qb->innerJoin('t.user','u','t.user = u.id')
                    ->orderBy($sort)
                    ->where('t.draft = false')
                    ->setFirstResult($skip)
                    ->setMaxResults($maxResult)
                    ->orderBy($sort, $desc)
                    ->getQuery()
                    ->getResult();
    }
    // /**
    //  * @return Text[] Returns an array of Text objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Text
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
