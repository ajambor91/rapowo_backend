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

class PopularTexts extends BaseCommand {
    protected static $defaultName = 'cron:popular-text';
    private $userRepo;
    private $textRepo;
    private $mailer;
    private $manager;
    public function __construct(EntityManagerInterface $manager, MailingService $mailingService, UserRepository $userRepository, TextRepository $textRepository)
    {
        parent::__construct();
        $this->textRepo = $textRepository;
        $this->userRepo = $userRepository;
        $this->mailer = $mailingService;
        $this->manager = $manager;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $users = $this->userRepo->getUserWhoWantsEmail(Setting::POPULAR_TEXT);
        $text = $this->textRepo->getPopularTexts()[0];
        dump($text);
        $textEntity = $this->textRepo->find($text['id']);
        $event = new Event();
        $event->setText($textEntity)
                ->setType(Event::POPULAR_TEXT);
        $this->manager->persist($event);
        $this->manager->flush();
        $this->sendToNode(['popular-text', $event->getId()]);
        $data = $this->concatData($users, $text);
        $this->mailer->sendEmails($data, MailConsts::POPULAR_TEXT);
        die;
    }
}
