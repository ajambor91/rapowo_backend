<?php
namespace App\Controller\TextController;
use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Entity\Event;
use App\Entity\Lang;
use App\Entity\NotedText;
use App\Entity\Text;
use App\Entity\User;
use App\Repository\NotedTextRepository;
use App\Repository\ObservatorRepository;
use App\Repository\TextRepository;
use App\Repository\UserRepository;
use App\Service\NotedTextMaker;
use App\Service\TextMaker;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

/**
 * @Route("/text")
 */

class TextController extends BaseController{
    /**
     * @Route("/private/add-text", methods={"POST"}, name="add_text")
     * @param TextMaker $textMaker
     * @param TextRepository $textRepository
     * @return JsonResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */

    public function addText(TextMaker $textMaker, TextRepository $textRepository){
        $data = $this->getJSONContent();
        $user = $this->getCurrentUser();
        $requiredData = ['title','content'];
        if(!$this->checkRequiredIndex($data,$requiredData)){
            return new JsonResponse(['status'=>false,'data'=>'no required data'],400);
        }
        if(!$user){
            return new JsonResponse(['status'=>false,'data'=>'user not found'],401);
        }

        $data['lang'] = 'pl'; //TODO zaimplementować zmianę języków
        $data['slug'] = $this->removeUnallowedChars($data['title'], $data['lang'], $textRepository);
        $text = $textMaker->createText($data, $user);
        $event = new Event();
        $event->setType(Event::NEW_FOLLOWED)
            ->setUser($user)
            ->setText($text);
        $text->addEvent($event);
        $checker = $textRepository->addText($text);
        if(!$checker){
            return new JsonResponse(['status'=>false, 'data'=>'cannot add text'],500);
        }
        $eventId = $event->getId();
        $httpClient = HttpClient::create();
        $httpClient->request('GET',$_ENV['NODE_ADDR']."/new-text-not/$eventId");
        return new JsonResponse(['status'=>true, 'data'=>'text was added'],200);

    }
    private function removeUnallowedChars(string $string, string $lang, TextRepository $textRepository): string {
        if($arr = $textRepository->findBy(['title'=>$string])){
            $string.='-'.count($arr);
        }
        return str_replace(' ','-',str_replace(Lang::SPECIAL_CHARS[$lang], Lang::ALLOWED_CHARS,$string));
    }
    /**
     * @Route("/private/edit/{id}" , methods={"PUT"} ,name="update_text")
     */
    public function updateText(int $id, Request $request, TextRepository $textRepository){
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false,'data'=>'unauthorized'],401);
        }
        $data = $this->getJSONContent();
        if(!$this->checkRequiredIndex($data, ['title', 'content'])){
            return new JsonResponse(['status'=>false, 'data'=>'no required data'], 400);
        }
        if(!$text = $textRepository->findOneBy(['user'=>$currentUser->getId(), 'id'=>$id])){
            return new JsonResponse(['status'=>false,'data'=>'text not found'],404);
        }
        if($currentUser !=  $text->getUser()){
            return new JsonResponse(['status'=>true,'data'=>'permission denied'],401);
        }

        $text->setContent($data['content'])
            ->setTitle($data['title']);

        $checker = $textRepository->updateText($text);
        if($checker){
            return new JsonResponse(['status'=>true, 'data'=>'text was succesfully updated'],200);
        }else{
            return new JsonResponse(['status'=>false, 'data'=>'cannot update text'],500);
        }

    }
    /**
     * @Route("/private/get-draft/{slug}", methods={"POST"})
     * @param TextRepository $textRepository
     * @param TextMaker $textMaker
     * @param string|null $slug
     * @return JsonResponse
     */
    public function createDraft(TextRepository $textRepository,TextMaker $textMaker, string $slug = null): JsonResponse{
        $data = $this->getJSONContent();
        $currentUser = $this->getCurrentUser();
        if(!$currentUser){
            return new JsonResponse(['status'=>false,'data'=>'unauthorized'], 401);
        }
        $draft = $textRepository->findOneBy(['user'=>$currentUser->getId(), 'slug'=>$slug ?: null]);
        if($slug && $draft){
            $draft->setContent($data['content'])
                    ->setTitle($data['title']);
            $checker = $textRepository->updateText($draft);
        }elseif($slug && !$draft){
            return new JsonResponse(['status'=>false, 'data'=>'cannot find text'], 404);
        }elseif(!$slug){
            $data['lang'] = 'pl';
            $data['slug'] = isset($data['title']) && !empty($data['title']) ? $this->removeUnallowedChars($data['title'],Lang::LANG_PL, $textRepository) : md5($currentUser->getNick() . time());
            $draft = $textMaker->createText($data, $currentUser, Text::DRAFT);
            $checker = $textRepository->addText($draft);
        }
        if($checker){
            return new JsonResponse(['status'=>true,'data'=>'draft was saved'],200);
        }
        return new JsonResponse(['status'=>false],500);
    }

    /**
     * @Route("/private/get-drafts/{userId}" , methods={"GET"})
     * @param int $userId
     * @param TextRepository $textRepository
     * @return JsonResponse
     */
    public function getDrafts(int $userId, TextRepository $textRepository): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED0, ApiResp::UNAUTHORIZED0]);
        }
        if(!$drafts = $textRepository->findBy(['user'=>$currentUser->getId(),'draft'=>Text::DRAFT], ['createdAt'=>'DESC'])){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        $data = [];
        $i = 0;
        foreach ($drafts as $draft){
            $data[$i]['title'] = $draft->getTitle();
            $data[$i]['slug'] = $draft->getSlug();
            $data[$i]['content'] = $draft->getContent();
            $i++;
        }
        return new JsonResponse(['status'=>true,'data'=>$data], ApiResp::ALL_OK);
    }

    /**
     * @Route("/private/get-one-draft", methods={"GET"})
     * @param TextRepository $textRepository
     * @param string $draftSlug
     * @return JsonResponse
     */
    public function getOneDraft(TextRepository $textRepository, string $draftSlug): JsonResponse{
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED);
        }
        if(!$draftSlug){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NO_REQ_DATA], ApiResp::BAD_REQUEST);
        }
        if(!$draft = $textRepository->findOneBy(['slug'=>$draftSlug,'user'=>$currentUser->getId()])){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        return new JsonResponse(['status'=>true,'data'=>$draft], ApiResp::ALL_OK);
    }

    /**
     * @Route("/private/public-draft/{draftSlug}" , methods={"PUT"})
     * @param TextRepository $repository
     * @param string $draftSlug
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    public function publicDraft(TextRepository $repository, string $draftSlug, EntityManagerInterface $manager): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_UNAUTHORIZED],ApiResp::UNAUTHORIZED);
        }

        $data = $this->getJSONContent();
        if(!$draftSlug || !$this->checkRequiredIndex($data,['title','content'])){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NO_REQ_DATA], ApiResp::BAD_REQUEST);
        }
        if(!$draft = $repository->findOneBy(['slug'=>$draftSlug,'user'=>$currentUser->getId()])){
            return new JsonResponse(['status'=>false,'data'=>ApiResp::MSG_NOT_FOUND], ApiResp::NOT_FOUND);
        }
        $slug = $this->removeUnallowedChars($data['title'], Lang::LANG_PL,$repository);
        if($slug !== $draftSlug){
            $draft->setSlug($slug);
        }
        if($draft->getTitle() !== $data['title']){
            $draft->setTitle($data['title']);
        }
        if($draft->getContent() !== $data['content']){
            $draft->setContent($data['content']);
        }
        $draft->setCreatedAt(new \DateTime());
        $draft->setDraft(Text::NON_DRAFT);
        $manager->persist($draft);
        $manager->flush();
        return new JsonResponse(['status'=>true],ApiResp::ALL_OK);
    }
    /**
     * @Route("/get/{page}/{queue}", methods={"GET"}, name="get_texts")
     */
    public function getTexts(UserRepository $userRepository, TextRepository $textRepository, NotedTextRepository $notedTextRepository, Request $request, ObservatorRepository $observatorRepository,int $page = 1, string $queue = null){
        $user = $this->getCurrentUser();
        $textsOnPage = 20;
        $skip = ($page -1) * 20;
        if($queue === 'queue'){
            $texts = $textRepository->getTexts($skip,$textsOnPage, false);
        }else{
            $texts = $textRepository->getTexts($skip,$textsOnPage);
        }
        $additionalData['current_page'] = $page;
        $additionalData['count_pages'] = ceil($textRepository->countTexts() / 20);
        $data = $this->prepareSongData($texts, $user, $observatorRepository, $notedTextRepository, $userRepository);
        return new JsonResponse(['status'=>true,'data'=>$data, 'additional_data' => $additionalData],200);

    }
    /**
     * @Route("/private/like-text/{id}/{type}", methods={"PUT"}, name="like_text")
     * @param string $type
     * @param int $id
     * @param TextRepository $textRepository
     * @param NotedTextRepository $notedTextRepository
     * @return JsonResponse
     */
    public function likeText(string $type, int $id, TextRepository $textRepository,NotedTextRepository $notedTextRepository){
        $text = $textRepository->find($id);
        $text->setNote($type === 'like' ? $text->getNote()+1  : $text->getNote()-1);
        $user = $this->getCurrentUser();
        if(!$user){
            return new JsonResponse(['status'=>false, 'data'=>'permission denied'],401);
        }
        if(!$text){
            return new JsonResponse(['status'=>false,'data'=>'text was not found'],404);
        }
        $nT = $notedTextRepository->findOneBy(['user'=>$user->getId(), 'text'=>$text->getId(),'type' => NotedText::LIKES_TYPES[$type]]);
        if($nT){
            return new JsonResponse(['status'=>false, 'data'=>'you already noted this text'],400);
        }
        $noted = new NotedTextMaker();
        $notedText = $noted->makeNoted($user, $text, NotedText::LIKES_TYPES[$type]);
        $text->addNotedText($notedText);
        if($textRepository->updateText($text)){
            return new JsonResponse(['status'=>true, 'data'=>'text was noted'],200);
        }else{
            return new JsonResponse(['status'=>false, 'data'=>'cannot note text'],500);
        }

    }

    /**
     * @Route("/get-user-texts/{id}/{page}", methods={"GET"} , name="get_user_texts")
     */
    public function getTextsByUser(int $id, UserRepository $userRepository, int $page = 1, TextRepository $textRepository, NotedTextRepository $notedTextRepository, Request $request, ObservatorRepository $observatorRepository){
        $author = $userRepository->find($id);
        if(!$author){
            return new JsonResponse(['status'=>false,'data'=>'user was not found'],404);
        }
        $user = $this->getCurrentUser();
        $textsOnPage = 20;
        $skip = ($page -1) * $textsOnPage;
        $texts = $textRepository->getUserTexts($author,$skip,$textsOnPage);
        $data = $this->prepareSongData($texts, $user, $observatorRepository, $notedTextRepository, $userRepository);
        $additionalData['current_page'] = $page;
        $additionalData['count_pages'] = ceil($textRepository->countTexts() / 20);

        return new JsonResponse(['status'=>true,'data'=>$data, 'additionalData'=>$additionalData],200);

    }
    private function prepareSongData(array $texts, $user, ObservatorRepository $observatorRepository, NotedTextRepository $notedTextRepository, UserRepository $userRepository = null): array {
        $thumbnail = null;
        $background = null;
        $i = 0;
        $data = [];
        foreach ($texts as $text){
            if($userRepository){
                $thumbnail = $userRepository->getUserTextThumbnail($text->getUser());
                $background = $userRepository->getUserSongBackground($text->getUser());
            }
            $data[$i]['id'] = $text->getId();
            $data[$i]['title'] = $text->getTitle();
            $data[$i]['content'] = $text->getContent();
            $data[$i]['note'] = $text->getNote();
            $data[$i]['created_at'] = date_format($text->getCreatedAt(),'d.m.Y');
            $data[$i]['updated_at'] = date_format($text->getUpdatedAt(),'d.m.Y');
            $data[$i]['slug'] = $text->getSlug();
            if($user){
                foreach ($notedTextRepository->getMainNotedTextByUser($user, $text) as $note) {
                    $data[$i]['note_type'] = $note->getType();
                }
                $checkObservator = $observatorRepository->checkObservator($text->getUser(),$user);
                $data[$i]['user']['followed'] = $checkObservator ? true : false;
            }
            $data[$i]['user']['id'] = $text->getUser()->getId();
            $data[$i]['user']['nick'] = $text->getUser()->getNick();
            $data[$i]['user']['sex'] = $text->getUser()->getSex();
            $data[$i]['user']['city'] = $text->getUser()->getCity();
            $data[$i]['user']['avatar'] = $thumbnail ?: null;
            $data[$i]['user']['created_at'] = date_format($text->getUser()->getCreatedAt(),'d.m.Y');
            $data[$i]['user']['background'] = $background ?: null;
            $data[$i]['user'] = $data[$i]['user'] + $this->getAdditionalData($text->getUser());
            $i++;
        }
        return $data;
    }

    /**
     * @Route("/song/{slug}", methods={"GET"})
     * @param string $slug
     * @param TextRepository $repository
     * @return JsonResponse
     */
    public function getTextBySlug(string $slug, TextRepository $repository, UserRepository $userRepository, ObservatorRepository $observatorRepository, NotedTextRepository $notedTextRepository): JsonResponse {
        $user = $this->getCurrentUser();
        $songData = $repository->findOneBy(['slug'=> $slug]);
        $data = $this->prepareSongData([$songData],$user,$observatorRepository, $notedTextRepository, $userRepository);
        if(!$songData){
            return new JsonResponse(['status'=>false,'data'=>'song not found'],404);
        }
        return new JsonResponse(['status'=>true, 'data'=>$data[0]],200);
    }

    /**
     * @param int $id
     * @param TextRepository $textRepository
     * @Route("/private/remove/{id}")
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function removeText(int $id, TextRepository $textRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if(!$user = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false, 'data'=>'user doesn\'t exist'], 401);
        }
        if(!$id || !$text = $textRepository->findOneBy(['id'=>$id, 'user'=>$user->getId()])){
            return new JsonResponse(['status'=>false, 'data'=>'text not found'],404);
        }
        $text->setSoftDelete(new \DateTime());
        try{
            $entityManager->persist($text);
            $entityManager->flush();
            return new JsonResponse(['status'=>true]);
        }
        catch (\Exception $exception){
            return new JsonResponse(['status'=>false],500);
        }

    }

    /**
     * @Route("/private/check-author/{slug}")
     * @param TextRepository $textRepository
     * @param string $slug
     * @return JsonResponse
     */
    public function checkAuthor(TextRepository $textRepository, string $slug): JsonResponse {
        if(!$currentUser = $this->getCurrentUser()){
            return new JsonResponse(['status'=>false,'data'=>'user not found'], 401);
        }
        if(!$data = $this->getJSONContent()){
            return new JsonResponse(['status'=>false,'data'=>'no required data'], 400);
        }
        if(!$text = $textRepository->findOneBy(['slug'=>$slug, 'user'=>$currentUser->getId()])){
            return new JsonResponse(['status'=>false, 'data'=>'not found'],404);
        }
        return new JsonResponse(['status'=>true],200);

    }

}
