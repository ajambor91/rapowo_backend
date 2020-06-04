<?php
namespace App\Controller\Admin;
use App\Constants\ApiResp;
use App\Controller\BaseController;
use App\Repository\TextRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TextController
 * @package App\Controller\Admin
 * @Route("/admin/text")
 */
class TextController extends BaseController {
    /**
     * @Route("/get/{page}/{queue}", methods={"GET"}, name="get_texts_admin")
     * @param int $page
     * @param bool $queue
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getTexts(int $page = 1, string $queue = null){
        return $this->redirectToRoute('get_texts',
            [
                'page' => $page,
                'queue' => $queue
            ]);
    }

    /**
     * @Route("/get-sorted/{queue}/{page}", methods={"GET"})
     * @param Request $request
     * @param TextRepository $textRepository
     * @param int $page
     * @return JsonResponse
     */
    public function getSortedText(Request $request, TextRepository $textRepository, int $page = 1): JsonResponse {
        $textsOnPage = 20;
        $skip = ($page -1) * 20;
        $sort = $request->query->get('sort');
        $type = $request->query->get('type');
        $texts = $textRepository->getSortedTexts($sort, $skip, $textsOnPage, $type);
        $data = [];
        $i = 0;
        foreach ($texts as $text){
            $data[$i]['id'] = $text->getId();
            $data[$i]['title'] = $text->getTitle();
            $data[$i]['content'] = $text->getContent();
            $data[$i]['created_at'] = ($text->getCreatedAt())->format('d.m.Y');
            $data[$i]['user']['nick'] = $text->getUser()->getNick();
            $i++;
        }
        $additionalData = [];
        $additionalData['current_page'] = $page;
        $additionalData['count_pages'] = $textRepository->countTexts();
        return new JsonResponse(['status'=>true, 'data'=>$data, 'additionalData'=>$additionalData, 'type' => $type], ApiResp::ALL_OK);

    }



}
