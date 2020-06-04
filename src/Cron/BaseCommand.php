<?php
namespace App\Cron;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpClient\HttpClient;

class BaseCommand extends Command{
    protected static $defaultName = 'cron:base-command';

    protected function sendToNode(array $path){
        $httpClient = HttpClient::create();
        $preparedPath = $_ENV['NODE_ADDR'].'/'.implode('/',$path);
        $httpClient->request('GET',$preparedPath);
    }
    protected function concatData(array $users, array $text): array {
        $i = 0;
        $data = [];
        foreach ($users as $user){
            $data[$i]['email'] = $user['email'];
            $data[$i]['title'] = $text['title'];
            $data[$i]['receiver_nick'] = $user['receiver'];
            $data[$i]['path'] = $text['path'];
            $data[$i]['author_nick'] = $text['nick'];
            $data[$i]['slug'] = $text['slug'];
            $i++;
        }
        return $data;
    }
}
