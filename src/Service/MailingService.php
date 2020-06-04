<?php

namespace App\Service;

use App\Constants\MailConsts;
use App\Entity\Event;
use Psr\Container\ContainerInterface;
use Twig\Environment;

class MailingService{
    const MAIL_PREFIX = 'mail/';
    const MAIL_SUFIX = '.html.twig';
    private $mailer;
    private $container;
    private $template;
    public function __construct(\Swift_Mailer $mailer, ContainerInterface $container, Environment $extension)
    {
        $this->container = $container;
        $this->mailer = $mailer;
        $this->template = $extension;

    }
    private function concatSource($type){
        return self::MAIL_PREFIX . MailConsts::MAIN_MAILING_TYPES[$type] . self::MAIL_SUFIX;
    }
    public function sendEmails($data, $type){
        $dataArr= [];
        if(!is_array($data)){
            $dataArr[] = (array)$data;
        }else{
            $dataArr = $data;
        }
        $message = new \Swift_Message(MailConsts::MAIN_MAILING_SUBJECTS[$type]);

        $cid = $message->embed(\Swift_Image::fromPath($_ENV['PUBLIC_ABS_DIR'] . 'assets/napis.png'));
        $viewSource = $this->concatSource($type);
        try {
            foreach ($dataArr as $datum) {
                if($type !== MailConsts::ACCOUNT_ACTIVATION && $type !== MailConsts::RESET_PASSWORD){
                    $datum['avatar'] = $message->embed(\Swift_Image::fromPath(isset($datum['path']) && $datum['path'] ? $_ENV['PUBLIC_ABS_DIR']  . $datum['path'] : $_ENV['PUBLIC_ABS_DIR']  . 'assets/default-avatar.png'));
                }
                $datum['cid'] = $cid;
                $message->setFrom($this->container->getParameter('sender'))
                    ->setTo($datum['email'])
                    ->setBody($this->template->render($viewSource, $datum), 'text/html');
                $this->mailer->send($message);
            }
            return true;
        }catch (\Exception $e){
            dump($e);die;
            return false;
        }

    }
}
