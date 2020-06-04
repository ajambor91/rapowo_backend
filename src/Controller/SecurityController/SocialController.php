<?php
namespace App\Controller\SecurityController;

use App\Controller\BaseController;
use App\Entity\Agreement;
use App\Entity\Image;
use App\Entity\Lang;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EventListeners\LoginListener;
use App\Service\SettingFactory;
use App\Service\UserMaker;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PhpParser\Node\NullableType;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class SocialController
 * @package App\Controller\SecurityController
 * @Route("/social")
 */
class SocialController extends BaseController{
    /**
     * @Route("/login", methods={"POST"})
     */
    public function login(AuthenticationSuccessHandler $authenticationSuccessHandler, LoginListener $listener, Request $request, UserRepository $userRepository, JWTTokenManagerInterface $manager, UserProviderInterface $provider){
        $user = null;
        $requiredData = [
            'email',
            'socialId',
            'type'
        ];
        $data = $this->getJSONContent();
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=> false, 'data' => 'no required data'], 400);
        }
        $socialUser = $userRepository->checkIsSocialExist($data['socialId']);
        if(!$socialUser){
            $socialUser = $this->registrySocialUser($data, $userRepository);
            if(!$socialUser)    {
                return new JsonResponse(['status'=> false], 500);
            }
        }
        $email = is_array($socialUser) ? $socialUser[0]->getEmail() : $socialUser->getEmail() ?: null;
        if(!$email){
            $socialUser = is_array($socialUser) ? $socialUser[0] : $socialUser;
            $avatar = $userRepository->getUserMainAvatarByType($socialUser, Image::TYPE_ORIGINAL);
            $background = $userRepository->getUserMainBackground($socialUser);
            $navbar = $userRepository->getUserMainAvatarByType($socialUser, Image::TYPE_NAVBAR_THUMB);
            $images = [$avatar, $background, $navbar['path']];
            $userData = $this->prepareUserData($socialUser, null, $images);
            return new JsonResponse(['status'=> true, 'data' => $userData],200);
        }
        $socialUser = $provider->loadUserByUsername($email);
        if( $socialUser && $token = $manager->create($socialUser)){
            $avatar = $userRepository->getUserMainAvatarByType($socialUser, Image::TYPE_ORIGINAL);
            $background = $userRepository->getUserMainBackground($socialUser);
            $navbar = $userRepository->getUserMainAvatarByType($socialUser, Image::TYPE_NAVBAR_THUMB);
            $images = [$avatar, $background, $navbar['path']];
            $userData = $this->prepareUserData($socialUser, $token, $images);
            $authenticationSuccessHandler->handleAuthenticationSuccess($socialUser, $token);
            return new JsonResponse(['status'=> true, 'data' => $userData],200);
        }
        return new JsonResponse(['status' => false],500);

    }

    private function registrySocialUser($data,UserRepository $userRepository){
        if($userRepository->findOneBy(['email' => $data['email'], 'softdeleta' => null])){
           $data['email'] = null;
        }
        $data['lang'] = 'pl'; //TODO zaimplementować zmianę języków
        $userMaker = new UserMaker($this->getDoctrine()->getRepository(Lang::class));
        $user = $userMaker->createSocialUser($data);
        $checker = $userRepository->addUser($user);
        if($checker){
            return $user;
        } else{
            return false;
        }
    }
    /**
     * @Route("/add-nick/{id}" , methods={"PUT"})
     */
    public function addUserNick(int $id, UserRepository $userRepository){
        $data = $this->getJSONContent();
        if(!$data || !$this->checkRequiredIndex($data, ['nick','email'])){
            return new JsonResponse(['status'=>false], 400);
        }
        /**
         * @param User $user
         */
        $user = $userRepository->findOneBy(['email' => $data['email'], 'softdeleta'=>null]);
        if(!$user || $user->getId() !== $id){
            return new JsonResponse(['status'=>false],401);
        }
        $user->setNick($data['nick']);
        $this->addAgreements($user, $data);
        $checker = $userRepository->updateUser($user);
        if($checker){
            return new JsonResponse(['status'=>true],200);
        }
        return new JsonResponse(['status'=>false],500);
    }

    /**
     * @Route("/additional/{id}", methods={"PUT"})
     * @param int $id
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function addAdditionalData(AuthenticationSuccessHandler $authenticationSuccessHandler,int $id, UserRepository $userRepository, UserProviderInterface $provider, JWTTokenManagerInterface $manager){
        $data = $this->getJSONContent();
        $requiredData = [
            'nick',
            'email',
            'socialId'
        ];
        if(!$this->checkRequiredIndex($data, $requiredData)){
            return new JsonResponse(['status'=> false], 400);
        }
        /**
         * @param User $user
         */
        $user = $userRepository->getByIdAndSocialIds($data);
        $user = is_array($user) ? $user[0] : $user;

        if(!$user || $user->getId() !== $id){
            return new JsonResponse(['status'=> false], 401);
        }
        $user->setNick($data['nick'])
                ->setEmail($data['email']);
        $this->addAgreements($user, $data);

        $checker = $userRepository->updateUser($user);
        if($checker){
            $socialUser = $provider->loadUserByUsername($user->getEmail());
            $token = $manager->create($socialUser);
            $data = $this->prepareUserData($socialUser, $token);
            $authenticationSuccessHandler->handleAuthenticationSuccess($socialUser, $token);

            return  new JsonResponse(['status' => true, 'data' => $data],200);
        }
        return new JsonResponse(['status'=>false],500);

    }
    private function addAgreements($user, $data){
        $ruleAgreement = new Agreement();
        $ruleAgreement->setType(Agreement::TYPE_ACCEPT_RULE);
        $user->addAgreement($ruleAgreement);
        if(isset($data['agreement']) && $data['agreement'] === true){
            $setting = new SettingFactory();
            foreach (Setting::MAIN_MAILING_TYPES as $key => $type){
                $user->addSetting($setting->createSetting($user, $key));
            }
        }
    }
}
