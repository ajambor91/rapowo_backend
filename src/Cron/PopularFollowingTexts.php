<?php
namespace App\Cron;

use App\Constants\MailConsts;
use App\Entity\Event;
use App\Repository\TextRepository;
use App\Repository\UserRepository;
use App\Service\MailingService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopularFollowingTexts extends BaseCommand {
    protected static $defaultName = 'cron:popular-following';
    private $repo;
    private $textRepo;
    private $em;
    private $mailer;
    public function __construct(MailingService $mailingService, EntityManagerInterface $manager, UserRepository $repository, TextRepository $textRepository)
    {
        parent::__construct();
        $this->repo = $repository;
        $this->textRepo = $textRepository;
        $this->em = $manager;
        $this->mailer = $mailingService;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->repo->getPopularFollowedUsersToSendEmail();
        $text = $this->textRepo->find($data[0]['text_id']);
        $event = new Event();
        $event->setText($text)
                ->setType(Event::POPULAR_FOLLOWED);
        $this->em->persist($event);
        $this->em->flush();
        $this->sendToNode(['popular-followed', $event->getId()]);
        dump($data);
        $this->mailer->sendEmails($data, MailConsts::POPULAR_FOLLOWED);
        die;
    }
}
