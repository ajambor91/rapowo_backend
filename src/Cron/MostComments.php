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
class MostComments extends BaseCommand {

    private $textRepo;
    private $userRepo;
    private $manager;
    private $mailer;
    protected static $defaultName = 'cron:most-commented';
    public function __construct(MailingService $mailingService, UserRepository $userRepository, TextRepository $textRepository, EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->userRepo = $userRepository;
        $this->textRepo = $textRepository;
        $this->manager = $manager;
        $this->mailer = $mailingService;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = $this->textRepo->getMostCommented()[0];
        $textObj = $this->textRepo->find($text['id']);
        $event = new Event();
        $event->setType(Event::MOST_COMMENTED)
                ->setText($textObj);
        $this->manager->persist($event);
        $this->manager->flush();
        $this->sendToNode([Event::MAIN_MAILING_TYPES[Event::MOST_COMMENTED], $event->getId()]);
        $users = $this->userRepo->getUserWhoWantsEmail(Setting::MOST_COMMENTED);
        $data = $this->concatData($users, $text);
        $this->mailer->sendEmails($data, MailConsts::MOST_COMMENTED);
        die;
    }
}
