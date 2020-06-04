<?php
namespace App\Controller\Admin;

use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Admin
 * @Route("/admin/user")
 */
class UserController extends BaseController{
    /**
     * @return JsonResponse
     * @Route("/get-users/{page}")
     */
    public function getUsers(UserRepository $userRepository, int $page = 1): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        if($currentUser->getRoles() !== User::ROLE_ADMIN){
            new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }

        return new JsonResponse(['status'=>$this->getCurrentUser()->getRoles()],200);
    }
}
