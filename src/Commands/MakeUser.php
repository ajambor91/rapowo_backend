<?php
namespace App\Commands;
use App\Repository\UserRepository;
use App\Service\UserMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeUser extends Command{
    protected static $defaultName = 'command:make-user';
    private $userRepo;
    private $userMaker;
    public function __construct(UserRepository $userRepository, UserMaker $userMaker)
    {
        parent::__construct();
        $this->userMaker = $userMaker;
        $this->userRepo = $userRepository;
    }
    protected function configure()
    {
        $this->addArgument('username', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $data = [
            'nick' => $input->getArgument('username'),
            'email' => $input->getArgument('email'),
            'passwords' => [
                'password' => $input->getArgument('password'),
                'repeatPassword' => $input->getArgument('password')
            ],
            'lang' => 'pl'
        ];

        $user = $this->userMaker->createUser($data);
        $user->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true);

        $check = $this->userRepo->addUser($user);
        $output->writeln($check ? 'Admin został dodany' : 'Coś poszło nie tak');
        die;

    }
}
