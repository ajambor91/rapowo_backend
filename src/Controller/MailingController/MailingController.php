<?php
namespace App\Controller\MailingController;
use App\Constants\ApiResp;
use App\Constants\MailConsts;
use App\Controller\BaseController;
use App\Entity\Event;
use App\Service\MailingService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MailingController
 * @package App\Controller\MailingController
 * @Route("/mailing")
 */
class MailingController extends BaseController{


    /**
     * @Route("/new-text", methods={"POST"}, name="new_text_mail")
     * @param MailingService $mailingService
     * @return JsonResponse
     */
    public function sendNewTextNotificationEmail(MailingService $mailingService): JsonResponse {
        $data = $this->getJSONContent();
        $sendMail = $mailingService->sendEmails($data, MailConsts::NEW_FOLLOWED);
        if($sendMail=== true) {
            return new JsonResponse(['status' => true], ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false,],ApiResp::INTERNAL_ERROR);
    }

    /**
     * @Route("/new-comment", methods={"POST"})
     * @param MailingService $mailingService
     * @return JsonResponse
     */
    public function newCommentToText(MailingService $mailingService): JsonResponse {
        $data = $this->getJSONContent();
        $sendMail = $mailingService->sendEmails($data, MailConsts::NEW_COMMENT_FOR_USER);
        if($sendMail=== true) {
            return new JsonResponse(['status' => true], ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false,],ApiResp::INTERNAL_ERROR);
    }

    /**
     * @Route("/reply-comment", methods={"POST"})
     * @param MailingService $mailingService
     * @return JsonResponse
     */
    public function replyComment(MailingService $mailingService): JsonResponse {
        $data = $this->getJSONContent();
        $sendMail = $mailingService->sendEmails($data, MailConsts::REPLY_COMMENT);
        if($sendMail=== true) {
            return new JsonResponse(['status' => true], ApiResp::ALL_OK);
        }
        return new JsonResponse(['status'=>false,],ApiResp::INTERNAL_ERROR);
    }

}
