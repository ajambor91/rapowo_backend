<?php
namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Lang;
use App\Entity\Observator;
use App\Entity\Text;
use App\Entity\User;
use App\Repository\ObservatorRepository;
use App\Repository\TextRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BaseController extends AbstractController {
    protected $request;
    /**
     * @var JWTEncoderInterface
     */
    protected $jwtAuth;
    /**
     * ApiController constructor.
     * @param JWTEncoderInterface $jwtAuth
     */
    public function __construct(JWTEncoderInterface $jwtAuth,RequestStack $request)
    {
        $this->jwtAuth = $jwtAuth;
        $this->request = $request->getCurrentRequest();
    }

    /**
     * @uses \Symfony\Component\HttpFoundation\Request
     * @return mixed
     */
    protected function getJSONContent(){
        $request = Request::createFromGlobals();
        return  json_decode($request->getContent(), true);
    }

    /**
     * @param Request $request
     * @return object|null
     */
    protected function getCurrentUser()
    {
        try {
            $token = $this->request->headers->get('Authorization');
            $token = str_replace('Bearer ', '', $token);
            $decodedToken = $this->jwtAuth->decode($token);
            $userFromDatabase = $this->getDoctrine()->getRepository(User::class)
                ->findOneBy(['email' => $decodedToken['email']]);
            return $userFromDatabase;

        }
        catch (\Exception $e){
            return null;
        }
    }

    /**
     * @param $data array
     * @param $index array
     * @return bool
     */
    protected function checkRequiredIndex(array $data, array $index){
        foreach ($index as $ind){
            if(!array_key_exists($ind,$data) || $data[$ind] == null){
                return false;
            }
        }
        return true;
    }
    protected function prepareUserData($user,$token = null, $images = null){
        $birthDate = $user->getBirthdate();
        $userData = [];
        $userData['name'] = $user->getName();
        $userData['id'] = $user->getId();
        $userData['token'] = $token;
        $userData['nick'] = $user->getNick();
        $userData['email'] = $user->getEmail() ?: null;
        $userData['birthdate'] = $birthDate !== null ? $birthDate->format('d-m-Y') : null;
        $userData['timestamp'] = $birthDate !== null ? $birthDate->getTimestamp() : null;
        $userData['sex'] = $user->getSex();
        $userData['city'] = $user->getCity();
        $userData['avatar']['path'] =  isset($images) && is_array($images ?? null)  ? $images[0] : null;
        $userData['background']['path'] = isset($images) && is_array($images ?? null)  ? $images[1] : null;
        $userData['navbar']['path'] = isset($images) && is_array($images ?? null)  ? $images[2] : null;
        $userData['socialId'] = $user->getGoogleId() ?: $user->getFbId() ?: null;
        return $userData;
    }
    protected function checkObserve(array $data, ObservatorRepository $observatorRepository, $currentUser, User $followedUser): array {
        $data['followed'] = $observatorRepository->findOneBy(['user'=>$followedUser->getId(), 'observator'=>$currentUser->getId()]) ? true : false;
        return $data;
    }
    protected function getAdditionalData(User $user){
        $textRepository = $this->getDoctrine()->getRepository(Text::class);
        $followerRepo = $this->getDoctrine()->getRepository(Observator::class);
        $commentRepo = $this->getDoctrine()->getRepository(Comment::class);
        $userAdditionalData['additional']['texts_sum'] = $textRepository->countUserText($user)[1];
        $userAdditionalData['additional']['notes'] = $textRepository->getUserNotes($user)[1];
        $userAdditionalData['additional']['followers_sum'] = $followerRepo->countFollowers($user)[1];
        $userAdditionalData['additional']['create_date'] = $user->getCreatedAt() ? $user->getCreatedAt()->format('d.m.Y') : null;
        $userAdditionalData['additional']['comments_sum'] = $commentRepo->countCommentsByUser($user)[1];
        $userAdditionalData['additional']['comment_for_user'] = $textRepository->countCommentForUser($user)[1];
        $userAdditionalData['additional']['lastLoginDate'] = $user->getLastLoginDate() ? $user->getLastLoginDate()->format('d.m.Y') : null;
        return $userAdditionalData;
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function checkIsAdmin(User $user): bool {
        return $user->getRoles() === User::ROLE_ADMIN ? true : false;
    }

}
