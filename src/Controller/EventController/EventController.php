<?php
namespace App\Controller\EventController;
use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/events")
 * Class EventController
 * @package App\Controller\EventController
 */
class EventController extends BaseController {
    /**
     * @Route("/get/{userId}", methods={"GET"})
     * @param EventRepository $repository
     * @param int $userId
     * @return JsonResponse
     */
    public function getEvents(EventRepository $repository, int $userId): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=> ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        $events = $repository->getLastEvents($currentUser);
        $data = [];
        $i = 0;
        foreach ($events as $event){
            $data[$i]['status'] = Event::MAIN_MAILING_TYPES[$event['event_type']];
            $data[$i]['data'] = $event;
            $i++;
        }
        $countEvents = ($repository->countUnreadEvents($currentUser))[1];
        return new JsonResponse(['status'=> true, 'data'=>$data,'unread_events'=>$countEvents], ApiResp::ALL_OK);
    }

    /**
     * @Route("/mark-as-read/{userId}", methods={"GET"})
     * @param EventRepository $repository
     * @param int $userId
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    public function markAsRead(EventRepository $repository, int $userId, EntityManagerInterface $manager): JsonResponse {
        if(!$currentUser = $this->getCurrentUser() ){
            return new JsonResponse(['status'=>false, 'data'=> ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        if($currentUser->getId() !== $userId){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        $events = $repository->getEventsToSetRead($currentUser);
        foreach ($events as $event){
            $event->setIsRead(true);
            $manager->persist($event);
            $manager->flush();
        }
        return new JsonResponse(['status'=>true], ApiResp::ALL_OK);

    }

    /**
     * @Route("/get-all-events/{userId}/{page}" ,methods={"GET"})
     * @param EventRepository $repository
     * @param int $userId
     * @param int $page
     * @return JsonResponse
     */
    public function getEventsToNotificationsComponent(EventRepository $repository, int $userId, int $page = 1): JsonResponse {
        if(!$currentUser =$this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED);
        }
        if($currentUser->getId() !== $userId){
            return new JsonResponse(['status'=>false, 'data'=>ApiResp::MSG_UNAUTHORIZED], ApiResp::UNAUTHORIZED);
        }
        $arr = [];
        $i = 0;
        $skip = Event::SKIP_EVENTS * ($page - 1);
        $data = $repository->getAllEvents($currentUser, $skip);
        foreach ($data as $datum){
            $arr[$i]['status'] = Event::MAIN_MAILING_TYPES[$datum['event_type']];
            $arr[$i]['data'] = $datum;
            $i++;
        }

        return new JsonResponse(['status'=>true, 'data'=>$arr], ApiResp::ALL_OK );
    }
}
