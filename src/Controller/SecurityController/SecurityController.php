<?php
namespace App\Controller\SecurityController;
use App\Constants\MailConsts;
use App\Controller\BaseController;
use App\Entity\Image;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\ImageRepository;
use App\Repository\ObservatorRepository;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Service\ImagePrepare;
use App\Service\MailingService;
use App\Service\UserMaker;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/user")
 * Class SecurityController
 * @package App\Controller\SecurityController
 */
class SecurityController extends BaseController{

    /**
     * @Route("/register", methods={"POST"}, name="register")
     * @param MailingService $mailingService
     * @param UserMaker $userMaker
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @uses \Symfony\Component\HttpFoundation\Request
     */
    public function registerUser(MailingService $mailingService, UserMaker $userMaker, UserRepository $userRepository, EntityManagerInterface $em){

        $data = $this->getJSONContent();
        $requiredData = ['email','nick','passwords'];
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'],400);
        }
        $userByNick = $userRepository->findOneBy(['nick' => $data['nick'], 'softdeleta' => null]);
        $userByEmail = $userRepository->findOneBy(['email' => $data['email'], 'softdeleta' => null]);
        if($userByEmail || $userByNick){
            return new JsonResponse(['status'=>false, 'data'=>'user exists'],409);
        }
        $data['lang'] = 'pl'; //TODO zaimplementować wybór języków
        unset($userByNick, $userByEmail);
        $user = $userMaker->createUser($data);
        if(isset($data['avatar']) && !empty($data['avatar']) && !empty($data['avatar']['path'])){
            $image = new ImagePrepare($data['avatar'], $user, $em, Image::SIZES[Image::TYPE_ORIGINAL], Image::TYPE_ORIGINAL);
            $user = $image->prepareImage();
            unset($image);

        }
        if(isset($data['background']) && !empty($data['background'] && !empty($data['background']['path'])) ){
            $image = new ImagePrepare($data['background'], $user, $em, Image::SIZES[Image::TYPE_BACKGROUND_ORIGINAL], Image::TYPE_BACKGROUND_ORIGINAL);
            $user = $image->prepareImage();
            unset($image);
        }
        $checker = $userRepository->addUser($user);
        if($checker ===  true){
            $data = new \StdClass();
            $data->hash = $user->getHash();
            $data->receiver_nick = $user->getNick();
            $data->email = $user->getEmail();
            $mailingService->sendEmails($data, MailConsts::ACCOUNT_ACTIVATION);
            return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false,'data'=>'user exists'],409);
        }

    }

    /**
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     * @Route("/login", methods={"PUT"} , name="user_login")
     */
    public function loginUser(AuthenticationSuccessHandler $authenticationSuccessHandler, UserRepository $userRepository, ImageRepository $imageRepository){
        $data = $this->getJSONContent();
        $requiredData = ['email','password'];
        if(!$this->checkRequiredIndex($data, $requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'], 400);
        }
        $user = $userRepository->findOneBy(['email'=>$data['email'], 'softdeleta'=>null]);
        if(!$user){
            return new JsonResponse(['status'=>false,'data'=>'user doesn\'t exist'],404);
        }
        if(!$user->getIsActive()){
            return new JsonResponse(['status'=>false,'data'=>'account is inactive'],401);
        }
        $checker = password_verify($data['password'],$user->getPassword());
        if($checker){
            $avatar = $imageRepository->findOneBy(['user'=>$user->getId(), 'type'=>Image::TYPE_MAIN]);
            $background = $imageRepository->findOneBy(['user'=>$user->getId(), 'type'=>Image::TYPE_BACKGROUND_CROPPED]);
            $avatarPath = $avatar ? $avatar->getPath() : null;
            $bckPath = $background ? $background->getPath() : null;
            $navbar = $userRepository->getUserMainAvatarByType($user, Image::TYPE_NAVBAR_THUMB);
            $images = [$avatarPath, $bckPath, $navbar['path']];
            $token = $this->jwtAuth->encode(['email'=>$data['email'], 'exp'=>time() + 60480000]);
            $userData = $this->prepareUserData($user,$token, $images);
            $authenticationSuccessHandler->handleAuthenticationSuccess($user, $token);
            $userData['mainAvatar'] = $userRepository->getUserMainAvatarByType($user, Image::TYPE_ORIGINAL);
            $userData['mainBackground'] = $userRepository->getUserMainBackground($user);
            return new JsonResponse(['status'=>true,'data'=>$userData],200);
        }
        else{
            return new JsonResponse(['status'=>false, 'data'=>'invalid password'],401);
        }
    }
    /**
     * @Route("/reset-password/{hash}", methods={"PUT"}, name="reset_password")
     * @param UserRepository $userRepository
     * @param string $hash
     * @return JsonResponse
     * @uses \Symfony\Component\HttpFoundation\Request
     */
    public function resetPassword(MailingService $mailingService, UserRepository $userRepository, string $hash, EntityManagerInterface $em){
        $data = $this->getJSONContent();
        $requiredData = ['password', 'repeatPassword'];
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'],400);
        }
        if($data['password'] !== $data['repeatPassword']){
            return new JsonResponse(['status'=>false],400);
        }
        $user = $userRepository->findOneBy(['hash'=>$hash]);
        if(!$user){
            return new JsonResponse(['status'=>true,'data'=>'User doesn\'t exsist'],404);
        }
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $userRepository->upgradePassword($user,$password);
        $checker = $userRepository->updateUser($user);
        if($checker){
            $user->setHash(null);
            $em->persist($user);
            $em->flush();
            return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false],500);
        }
    }

    /**
     * @Route("/edit-profile/{id}" , methods={"PUT"} ,name="edit_profile")
     * @param int $id
     * @param User $user
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @throws \Exception
     * @uses \Symfony\Component\HttpFoundation\Request
     */
    public function editProfile(UserRepository $userRepository, int $id,EntityManagerInterface $manager){
        $removeImages = function ($image, array $types, $user, $manager, $originalType){
            $images = $user->getImages();
            if($image['path'] || $image['removed'] || $image['size']['sizeX'] > 0 || $image['size']['sizeY'] > 0){
                foreach ($images as $item){
                    if(in_array($item->getType(), $types)){
                        $user->removeImage($item);
                        if(file_exists($item->getPath())) unlink($item->getPath());
                    }
                }
                if(!empty($image['path']) && !$image['removed']){
                    $image = new ImagePrepare($image,$user, $manager, Image::SIZES[$originalType],$originalType) ;
                    $user = $image->prepareImage();
                }
            }
        };
        $user = $this->getCurrentUser();
        if(!$user || $id !== $user->getId()){
            return new JsonResponse(['status'=>false],401);
        }
        $data = $this->getJSONContent();
        $password = function (array $passwords){
            if($passwords['password'] === $passwords['repeatPassword']){
                return password_hash($passwords['password'], PASSWORD_DEFAULT);
            }
            return false;
        };
        $user->setCity($data['city'])
            ->setSex($data['sex'])
            ->setName($data['name']);
        if(isset($data['birthdate']) && !empty($data['birthdate'])){
            $user->setBirthdate(new \DateTime(implode('-',$data['birthdate'])));
        }
        if((isset($data['avatar']['path']) && $data['avatar']['path']) || $data['avatar']['removed']){
            $types = [
              Image::TYPE_AUTHOR_THUMB,
              Image::TYPE_MAIN,
              Image:: TYPE_NAVBAR_THUMB,
              Image::TYPE_ORIGINAL
            ];
            $removeImages($data['avatar'], $types, $user, $manager, Image::TYPE_ORIGINAL);
        }
        if((isset($data['background']['path']) && $data['background']['path']) || $data['background']['removed']){
            $types = [
                Image::TYPE_BACKGROUND_CROPPED,
                Image::TYPE_BACKGROUND_SONG,
                Image::TYPE_BACKGROUND_ORIGINAL
            ];
            $removeImages($data['background'], $types, $user, $manager, Image::TYPE_BACKGROUND_ORIGINAL);
        }
        if(isset($data['email']) && !empty($data['email'])){
            $user->setEmail($data['email']);
        }
        if(isset($data['nick']) && !empty($data['nick'])){
            $user->setNick($data['nick']);
        }
        if(isset($data['passwords']['password']) && !empty($data['passwords']['password'])){
            $userRepository->upgradePassword($user,$password($data['passwords']));
        }
        $checker = $userRepository->updateUser($user);

        if($checker){
            $imgRepo = $this->getDoctrine()->getRepository(Image::class);
            $avatar = $imgRepo->findOneBy(['user'=>$user, 'type'=>Image::TYPE_MAIN]);
            $bck = $imgRepo->findOneBy(['user'=>$user, 'type'=>Image::TYPE_BACKGROUND_CROPPED]);
            $avPath = $avatar ? $avatar->getPath() : null;
            $bckPath = $bck ? $bck->getPath() : null;
            $navbar = $userRepository->getUserMainAvatarByType($user, Image::TYPE_NAVBAR_THUMB);
            $images = [$avPath, $bckPath,$navbar['path']];
            $token = $this->jwtAuth->encode(['email'=>$data['email'], 'exp'=>time() + 6004800]);
            $userData = $this->prepareUserData($user,$token, $images);
            $userData['mainAvatar'] = $userRepository->getUserMainAvatarByType($user, Image::TYPE_ORIGINAL);;
            $userData['mainBackground'] = $userRepository->getUserMainBackground($user);
            return new JsonResponse(['status'=>true, 'data'=>$userData],200);
        }
        else{
            return new JsonResponse(['status'=>false],500);
        }
    }


    /**
     * @param User $user
     * @param \Swift_Mailer $mailer
     */
    public function sendMessage(User $user, \Swift_Mailer $mailer){
        $msg = new \Swift_Message('Aktywacja konta');
        $msg->setFrom($this->getParameter('sender'))
            ->setTo($user->getEmail())
            ->setBody($this->renderView('mail/activation.html.twig',[
                'user'=>$user->getName(),
                'hash'=>$user->getHash()
            ]),'text/html');
        $mailer->send($msg);
    }

    /**
     * @Route("/activate/{hash}", methods={"PUT"}, name="activate")
     * @param UserRepository $userRepository
     * @param string $hash
     * @return JsonResponse
     */
    public function activate(UserRepository $userRepository, string $hash){
        $user = $userRepository->findOneBy(['hash'=>$hash,'isActive'=>0]);
        if(!$user){
            return new JsonResponse(['status'=>false,'data'=>'User doesn\'t exist'],404);
        }
        if($user->getIsActive()){
            return new JsonResponse(['status'=>false, 'data'=>'Account is active'],409);
        }
        $user->setIsActive(true)
            ->setHash(null);
        $checker = $userRepository->updateUser($user);
        if($checker){
            return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false],500);
        }
    }

    /**
     * @Route("/reset-password-msg", name="reset_password_msg", methods={"PUT"})
     * @param UserRepository $userRepository
     * @param \Swift_Mailer $mailer
     * @return JsonResponse
     * @throws \Exception
     * @uses \Symfony\Component\HttpFoundation\Request
     */
    public function resetPasswordMsg(MailingService $mailingService, UserRepository $userRepository, \Swift_Mailer $mailer, EntityManagerInterface $em){
        $data = $this->getJSONContent();
        $requiredData = ['email'];
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'],400);
        }
        $user = $userRepository->findOneBy(['email'=>$data['email']]);
        if(!$user){
            return new JsonResponse(['status'=>false,'user doesn\n exist'], 404);
        }
        $date = new \DateTime();
        $hash = sha1($date->getTimestamp().$user->getNick());
        $user->setHash($hash);
        $em->persist($user);
        $em->flush();
        $data = new \StdClass();
        $data->hash = $user->getHash();
        $data->receiver_nick = $user->getNick();
        $data->email = $user->getEmail();
        $mailingService->sendEmails($data, MailConsts::RESET_PASSWORD);
        return new JsonResponse(['status'=>true],200);


    }

    /**
     * @Route("/check-exist/{type}", name="check_exist", methods={"POST"})
     * @param string $type
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function checkIsExists(string $type,UserRepository $userRepository){
        $data = $this->getJSONContent();
        $requiredData = [$type];
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'], 400);
        }
        $checker = $userRepository->findOneBy([$type=>$data[$type], 'softdeleta'=>null]);
        if($checker){
            return new JsonResponse(['status'=>false,'data'=>'user exists'],409);
        }else{
            return new JsonResponse(['status'=>true],200);
        }
    }

    /**
     * @Route("/get-user-by-hash/{hash}", methods={"GET"}, name="get_user_by_hash")
     * @param UserRepository $userRepository
     * @param string $hash
     * @return JsonResponse
     */
    public function checkIsUserExistByHash(string $hash, UserRepository $userRepository){
        if(!$hash){
            return new JsonResponse(['status'=>false, 'no hash data'],400);
        }
        $user = $userRepository->findOneBy(['hash'=>$hash]);
        if($user){
           return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false,'data'=>'user not found'],404);
        }
    }

    /**
     * @Route("/delete-user/{id}" , methods={"POST"}, name="delete_user")
     * @param int $id
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function deleteUser(int $id, UserRepository $userRepository){
        $user = $this->getCurrentUser();
        if(!$user || $user->getId() !== $id){
            return new JsonResponse(['status'=>false,'data'=>'unathorized access'],401);
        }
        $data = $this->getJSONContent();
        $requiredData = ['password'];
        if((!$user->getFbId() && !$user->getGoogleId()) &&!password_verify($data['password'], $user->getPassword())){
            return new JsonResponse(['status'=>false,'data'=>'unathorized access'],401);
        }
        $checker = $userRepository->deleteUser($user);
        if($checker){
            return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false],500);
        }

    }
    /**
     * @Route("/settings/{id}", methods={"GET"}, name="get_mailing_settings")
     */
    public function getMailingSettings(int $id, SettingRepository $settingRepository){
        $user = $this->getCurrentUser();
        if((!$user || $user->getId() !== $id)){
            return new JsonResponse(['status'=>false,'data'=>'unauthorized'],401);
        }
        $settings = $user->getSettings()->toArray();
        $indexes = [
            'newText' => Setting::NEW_TEXT,
            'popularText'=>Setting::POPULAR_TEXT,
            'mostCommented'=>Setting::MOST_COMMENTED,
            'newFollowed'=>Setting::NEW_FOLLOWED,
            'popularFollowed'=>Setting::POPULAR_FOLLOWED,
            'newCommentForUser'=>Setting::NEW_COMMENT_FOR_USER
        ];
        $dataArr = [];
        foreach ($settings as $key => $setting){
            foreach ($indexes as $indexKey => $index){
                if($index === $setting->getType()){
                    $dataArr[$indexKey] = true;
                }elseif (!array_key_exists($indexKey, $dataArr)){
                    $dataArr[$indexKey] = false;
                }
            }
        }
        return new JsonResponse(['status'=>true, 'data'=>$dataArr],200);
    }

    /**
     * @Route("/save-settings/{id}", methods={"PUT"}, name="save_settings")
     */
    public function saveSettings(int $id){
        /**
         * @var User $user
         */
        $user = $this->getCurrentUser();
        if(!$user || $user->getId() !== $id){
            return new JsonResponse(['status'=>false, 'data'=>'unathorized'],401);
        }
        $data = $this->getJSONContent();
        $indexes = [
            'newText' => Setting::NEW_TEXT,
            'popularText'=>Setting::POPULAR_TEXT,
            'mostCommented'=>Setting::MOST_COMMENTED,
            'newFollowed'=>Setting::NEW_FOLLOWED,
            'popularFollowed'=>Setting::POPULAR_FOLLOWED,
            'newCommentForUser'=>Setting::NEW_COMMENT_FOR_USER
        ];
        $settings = $user->getSettings()->toArray();
        $dataArr = [];
        foreach ($data as $key => $datum){
            $settingExists = false;
            foreach ($settings as $setting){

                if($indexes[$key] === $setting->getType() && $datum === false){
                    $user->removeSetting($setting);
                }
                elseif ($indexes[$key] === $setting->getType() && $datum === true){
                    $settingExists = true;
                }
            }
            if($datum === true && !$settingExists){
                $newSetting = new Setting();
                $newSetting->setUser($user)
                            ->setType($indexes[$key]);
                $user->addSetting($newSetting);
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush($user);
        return new JsonResponse(['status'=>true],200);
    }
    /**
     * @Route("/get/{id}", methods={"GET"}, name="get_user_by_id")
     */
    public function getUserById(int $id, UserRepository $userRepository, ImageRepository $imageRepository, ObservatorRepository $observatorRepository){
        $user = $userRepository->find($id);
        if(!$user){
            return new JsonResponse(['status' => false, 'data' => 'user doesn\'t exist'], 404);
        }

        $avatar = $imageRepository->findOneBy(['user'=>$user->getId(), 'type'=>Image::TYPE_MAIN]);
        $backgroundImage = $imageRepository->findOneBy(['user'=>$user->getId(), 'type' => Image::TYPE_BACKGROUND_CROPPED]);
        $avatar = $avatar ? $avatar->getPath() : null;
        $backgroundImage = $backgroundImage ? $backgroundImage->getPath() : null;
        $navbar = $userRepository->getUserMainAvatarByType($user, Image::TYPE_NAVBAR_THUMB);
        $images = [$avatar, $backgroundImage, $navbar];
        $data = $this->prepareUserData($user, null, $images);
        $data = $data + $this->getAdditionalData($user);
        if($currentUser = $this->getCurrentUser()){
            $data = $this->checkObserve($data, $observatorRepository, $currentUser, $user);
        }
        return new JsonResponse(['status'=>true, 'data'=>$data],200);
    }



}
