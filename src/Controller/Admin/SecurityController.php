<?php
namespace App\Controller\Admin;
use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController
 * @package App\Controller\Admin
 * @Route("/admin")
 */
class SecurityController extends BaseController{
    /**
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @Route("/login", methods={"PUT"})
     */
    public function loginAsAdmin(UserRepository $userRepository): JsonResponse {
        $data = $this->getJSONContent();
        $requiredData = ['email','password'];
        if(!$this->checkRequiredIndex($data, $requiredData)){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NO_REQ_DATA], ApiResp::BAD_REQUEST);
        }
        $user = $userRepository->findOneBy(['email'=>$data['email'], 'softdeleta'=>null]);
        if(!$user){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND],ApiResp::NOT_FOUND);
        }
        if(!$user->getIsActive()){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED);
        }
        if(!$checker = password_verify($data['password'],$user->getPassword())){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        if(!$roles = $this->checkIsAdmin($user)){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        $token = $this->jwtAuth->encode(['email'=>$data['email'], 'exp'=>time() + 60480000]);
        $data = [
            'role' => 'admin',
            'nick' => $user->getNick(),
            'email' => $user->getEmail(),
            'id' => $user->getId(),
            'token' => $token
        ];
        return new JsonResponse(['status'=>true,'data'=>$data], ApiResp::ALL_OK);
    }

}
