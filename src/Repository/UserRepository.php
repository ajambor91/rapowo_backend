<?php

namespace App\Repository;

use App\Entity\DeletedUser;
use App\Entity\Image;
use App\Entity\Observator;
use App\Entity\Setting;
use App\Entity\Text;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    public function getUserMainAvatarByType(User $user, $type){
        $qb = $this->createQueryBuilder('u');
        try {
            return $qb->select('i.path')
                ->where('u.id = :userId')
                ->leftJoin(Image::class, 'i', 'with', 'u.id = i.user')
                ->andWhere('i.type = :type')
                ->setParameters(['userId' => $user->getId(), 'type' => $type])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function getUserTextThumbnail(User $user){
        $qb = $this->createQueryBuilder('u');
        try {
            return $qb->select('i.path')
                ->where('u.id = :userId')
                ->leftJoin(Image::class, 'i', 'with', 'u.id = i.user')
                ->andWhere('i.type = :type')
                ->setParameters(['userId' => $user->getId(), 'type' => Image::TYPE_AUTHOR_THUMB])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function getUserSongBackground(User $user){
        $qb = $this->createQueryBuilder('u');
        try {
            return $qb->select('i.path')
                ->where('u.id = :userId')
                ->leftJoin(Image::class, 'i', 'with', 'u.id = i.user')
                ->andWhere('i.type = :type')
                ->setParameters(['userId' => $user->getId(), 'type' => Image::TYPE_BACKGROUND_SONG])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    public function getUserMainBackground(User $user){
        $qb = $this->createQueryBuilder('u');
        try {
            return $qb->select('i.path')
                ->where('u.id = :userId')
                ->leftJoin(Image::class, 'i', 'with', 'u.id = i.user')
                ->andWhere('i.type = :type')
                ->setParameters(['userId' => $user->getId(), 'type' => Image::TYPE_BACKGROUND_ORIGINAL])
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     * @throws \Doctrine\ORM\ORMException
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        try{
            $this->_em->persist($user);
            $this->_em->flush();
        }
        catch (\Exception $exception){
            return;
        }
    }
    public function addUser($user){

        try{
            $this->_em->persist($user);
            $this->_em->flush();
            return true;
        }
        catch (\Exception $e){
            return false;
        }
    }
    public function updateUser($user){
        try{
            $this->_em->flush($user);
            return true;
        }
        catch (\Exception $exception){
            return false;
        }
    }
    public function deleteUser($user){
        try{
            $deletedUser = new DeletedUser();
            $deletedUser->setUser($user)
                        ->setEmail($user->getEmail());
            $this->_em->persist($deletedUser);
            $this->_em->flush();
            $user->setSoftdeleta(new \DateTime())
                ->setEmail(null);
            $this->_em->flush($user);
            return true;
        }
        catch (\Exception $e){
            return $e;
        }
    }
    public function checkIsSocialExist(string $socialId){
        $qb = $this->createQueryBuilder('u');
        return $qb->where('u.googleId = :id')
                    ->orWhere('u.fbId = :id')
                    ->andWhere('u.softdeleta IS NULL')
                    ->setParameter(':id', $socialId)
                    ->getQuery()
                    ->getResult();
    }
    public function checkIsEmailOrNickExist($user){
        $qb = $this->createQueryBuilder('u');
        return $qb->where('u.email = :email')
            ->orWhere('u.nick = :nick')
            ->setParameters([':nick' => $user->getNick(), ':email' => $user->getEmail()])
            ->getQuery()
            ->getResult();
    }
    public function getByIdAndSocialIds($data){
        $qb = $this->createQueryBuilder('u');
        return $qb->where('u.id = :id')
                    ->andWhere('u.fbId = :fbId OR u.googleId = :googleId')
                    ->setParameters(['id'=>$data['id'], 'fbId'=>$data['socialId'], 'googleId' => $data['socialId']])
                    ->getQuery()
                    ->getResult();
    }
    public function getPopularFollowedUsersToSendEmail(){
        $qb = $this->createQueryBuilder('u');
        return $qb->select(['distinct(u.id) as receiver_id','u.nick as receiver_nick','t.slug','t.id as text_id','u.email','IDENTITY(t.user) as author_nick','s.type','t.title','t.content','t.note as note'])
                    ->leftJoin('u.settings','s','WITH','u.id = s.user')
                    ->leftJoin(Observator::class, 'o','WITH','u.id = o.observator')
                    ->leftJoin(Text::class,'t','WITH','t.user = o.user')
                    ->where('s.type = :type')
                    ->andWhere('t.note > :note')
                    ->andWhere('t.createdAt < :now')
                    ->orderBy('t.note','DESC')
                    ->setParameters(['type'=> Setting::POPULAR_FOLLOWED, 'note'=>Text::POPULAR_LIMIT,'now'=>(new \DateTime())->modify('- 2 days')])
                    ->getQuery()
                    ->getResult();
    }
    public function getUserWhoWantsEmail(string $type = Setting::NEW_TEXT){
        $qb = $this->createQueryBuilder('u');
        return $qb->select(['distinct(u.id) as receiver_id','u.email','u.nick as receiver','s.type'])
                    ->innerJoin('u.settings','s','WITH','u.id = s.user')
                    ->where('s.type = :type')
                    ->setParameter('type', $type)
                    ->getQuery()
                    ->getResult();
    }
    public function getAllUsers(int $skip, int $limit): array {
        $qb = $this->createQueryBuilder('u');
        return $qb->select(['u.id', 'u.email','u.nick'])
                    ->setFirstResult($skip)
                ->setMaxResults($limit);
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
}
