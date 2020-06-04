<?php
namespace App\Service\EventListeners;


use App\Repository\UserRepository;
use http\Env\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoginListener{
    private $userRepo;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepo = $userRepository;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event){
        $user = $event->getUser();
        $user->setLastLoginDate(new \DateTime());
        $this->userRepo->updateUser($user);

    }

}
