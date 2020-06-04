<?php
namespace App\Controller\TextController;

use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\Observator;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\TextRepository;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentsController
 * @package App\Controller\TextController
 * @Route("/comments")
 */
class CommentsController extends BaseController{
    /**
     * @param TextRepository $textRepository
     * @param CommentRepository $commentRepository
     * @param int $id
     * @return JsonResponse
     * @Route("/private/add/{id}/{parentId}", methods={"POST"})
     */
    public function addComment(TextRepository $textRepository, CommentRepository $commentRepository, int $id, int $parentId = null): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED);
        }
        $data = $this->getJSONContent();
        if(!$this->checkRequiredIndex($data,['content'])){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_NO_REQ_DATA], ApiResp::BAD_REQUEST);
        }

        if(!$text = $textRepository->find($id)){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        if($parentId && !$parentComment = $commentRepository->find($parentId)){
            return  new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND],ApiResp::NOT_FOUND);
        }
        $httpClient = HttpClient::create();

        $event = new Event();
        $comment = new Comment();
        $comment->setContent($data['content'])
                ->setUser($currentUser);

        if($parentId && $parentComment){

            $parentComment->addComment($comment);
            $checker = $commentRepository->updateComment($comment);
            $event->setComment($comment)
                ->setType(Event::REPLY_COMMENT)
                ->setUser($currentUser)
                ->setText($text);
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            $httpClient->request('GET', $_ENV['NODE_ADDR'].'/reply-comment/'.$event->getId());

        }else{
            $event->setComment($comment)
                ->setType(Event::NEW_COMMENT_FOR_USER)
                ->setUser($currentUser);
            $text->addComment($comment)
                ->addEvent($event);
            $checker = $textRepository->updateText($text);
            if($checker){
                $httpClient->request('GET', $_ENV['NODE_ADDR'].'/new-comment/'.$event->getId());
            }

        }

        $data['id'] = $comment->getId();
        $data['content'] = $comment->getContent();
        $data['createdAt'] = $comment->getCreatedAt();
        $data['user'] = $this->createUserArray($comment->getUser(), $currentUser);
        if($checker){
            return new JsonResponse(['status'=>true, 'data'=>$data],ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false],ApiResp::INTERNAL_ERROR);
    }

    /**
     * @param TextRepository $textRepository
     * @param int $id
     * @param CommentRepository $commentRepository
     * @return JsonResponse
     * @Route("/get-comments/{id}", methods={"GET"})
     */
    public function getComments(TextRepository $textRepository, int $id, CommentRepository $commentRepository): JsonResponse {
        $data = [];
        $i = 0;
        $curentUser = $this->getCurrentUser();
        if(!$id || !$text = $textRepository->find($id)){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_NOT_FOUND],ApiResp::NOT_FOUND);
        }
        foreach ($commentRepository->getComments($id) as $comment){
            $data[$i]['id'] = $comment->getId();
            $data[$i]['content'] = $comment->getContent();
            $data[$i]['createdAt'] = $comment->getCreatedAt();
            $data[$i]['user'] = $this->createUserArray($comment->getUser(), $curentUser);
            if($commentRepository->getComments(null, $comment->getId())){
                $j = 0;
                foreach ($commentRepository->getComments(null, $comment->getId()) as $child){
                    $data[$i]['children'][$j]['id'] = $child->getId();
                    $data[$i]['children'][$j]['content'] = $child->getContent();
                    $data[$i]['children'][$j]['createdAt'] = $child->getCreatedAt();
                    $data[$i]['children'][$j]['user'] = $this->createUserArray($child->getUser(), $curentUser);
                    $j ++;

                }
            }
            $i ++;
        }
        return new JsonResponse(['status'=>true, 'data'=>$data],ApiResp::ALL_OK);

    }

    /**
     * @param User|null $user
     * @param User|null $currentUser
     * @return array
     */
    private function createUserArray(User $user = null, User $currentUser = null): array {
        $observatorRepo = $this->getDoctrine()->getRepository(Observator::class);
        $userRepo = $this->getDoctrine()->getRepository(User::class);
        if($userRepo && $user){
            $thumbnail = $userRepo->getUserTextThumbnail($user);
            $background = $userRepo->getUserSongBackground($user);
        }
        return [
            'id' => $user->getId(),
            'nick' => $user->getNick(),
            'sex' => $user->getSex(),
            'city' => $user->getCity(),
            'followed' => $currentUser && $observatorRepo->checkObservator($user, $currentUser) ? true : false,
            'avatar' => $thumbnail ?? null,
            'background' => $background ?? null

        ];
    }

    /**
     * @Route("/private/edit/{commentId}" , methods={"PUT"})
     * @param CommentRepository $commentRepository
     * @param int $commentId
     * @return JsonResponse
     */
    public function editComment(CommentRepository $commentRepository,  int $commentId): JsonResponse {
        if(!$curentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=> ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED );
        }
        $data = $this->getJSONContent();
        if(!$this->checkRequiredIndex($data, ['content'])){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_NO_REQ_DATA], ApiResp::BAD_REQUEST);
        }
        if(!$comment = $commentRepository->findOneBy(['user'=>$curentUser->getId(), 'id'=>$commentId])){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        $comment->setContent($data['content']);
        if($commentRepository->updateComment($comment)){
            return new JsonResponse(['status'=>true], ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false], ApiResp::INTERNAL_ERROR);
    }

    /**
     * @Route("/private/delete/{commentId}" , methods={"DELETE"})
     * @param CommentRepository $commentRepository
     * @param int $commentId
     * @return JsonResponse
     * @throws Exception
     */
    public function delComment(CommentRepository $commentRepository, int $commentId): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        if(!$comment = $commentRepository->findOneBy(['id'=>$commentId, 'user'=>$currentUser->getId()])){
            return new JsonResponse(['status'=> false, 'data' => ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        $comment->setSoftDelete(new \DateTime());
        if($commentRepository->updateComment($comment)){
            return new JsonResponse(['status'=>true], ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false], ApiResp::INTERNAL_ERROR);
    }


}
