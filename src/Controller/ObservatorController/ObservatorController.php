<?php
namespace App\Controller\ObservatorController;

use App\Controller\BaseController;
use App\Entity\Observator;
use App\Entity\User;
use App\Repository\ObservatorRepository;
use App\Repository\UserRepository;
use App\Service\ObservatorMaker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ObservatorController
 * @package App\Controller\ObservatorControlller
 * @Route("/follower")
 */
class ObservatorController extends BaseController{
    /**
     * @Route("/add/{id}", name="add", methods={"GET"})
     */
    public function addToObserve(int $id,UserRepository $userRepository, ObservatorRepository $observatorRepository ){
        $currentUser = $this->getCurrentUser();
        $user = $userRepository->findOneBy(['id'=>$id]);
        if(!$currentUser){
            return new JsonResponse(['status'=>false,'data'=>'permission denied'],403);
        }
        if(!$user){
            return new JsonResponse(['status'=>false,'data'=>'user was not found'],404);
        }
        $checkIsExist = $observatorRepository->findOneBy(['user'=>$user->getId(),'observator'=>$currentUser->getId()]);
        if($checkIsExist){
            return new JsonResponse(['status'=>false,'data'=>'conflict'],409);
        }
        $observator = new ObservatorMaker();
        $observator = $observator->createObservator($user, $currentUser);
        $checker = $observatorRepository->addObservator($observator);
        if($checker){
            return new JsonResponse(['status'=>true],200);
        }
        else{
            return new JsonResponse(['status'=>false, 'data'=>'cannot add to observe'],500);
        }
    }
    /**
     * @Route("/remove/{id}", name="remove_observator", methods={"DELETE"})
     */
    public function removeObservator(ObservatorRepository $observatorRepository, int $id, Request $request){
        $observator = $this->getCurrentUser();
        if(!$observator){
            return new JsonResponse(['status'=>false,'data'=>'permission denied'],403);
        }
        $user = $observatorRepository->findOneBy(['user'=>$id,'observator'=>$observator->getId()]);
        if(!$user){
            return new JsonResponse(['status'=>false,'user doesn\' exist'],404);
        }
        $checker = $observatorRepository->removeObservator($user);
        if($checker){
            return new JsonResponse(['status'=>true],200);
        }else{
            return new JsonResponse(['status'=>false, 'data'=>'cannot remove'],500);
        }
    }


}
