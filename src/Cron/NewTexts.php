<?php
namespace App\Cron;
use App\Constants\MailConsts;
use App\Entity\Event;
use App\Entity\Setting;
use App\Repository\TextRepository;
use App\Repository\UserRepository;
use App\Service\MailingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewTexts extends BaseCommand {
    protected static $defaultName = 'cron:new-text';
    private $repo;
    private $userRepo;
    private $em;
    private $mailer;
    public function __construct(MailingService $mailingService, TextRepository $textRepository, UserRepository $repository, EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->repo = $textRepository;
        $this->userRepo = $repository;
        $this->em = $manager;
        $this->mailer = $mailingService;

    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userRepo->getUserWhoWantsEmail(Setting::NEW_TEXT);
        $text = $this->repo->getNewTexts()[0];
        $textEntity = $this->repo->find($text['text_id']);
        $event = new Event();
        $event->setText($textEntity)
                ->setType(Event::NEW_TEXT);
        $this->em->persist($event);
        $this->em->flush();
        $this->sendToNode(['new-text',$event->getId()]);
        $data = $this->concatData($users, $text);
        $this->mailer->sendEmails($data, MailConsts::NEW_TEXT);
        die;
    }
}
