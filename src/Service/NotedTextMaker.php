<?php
namespace App\Service;
use App\Entity\NotedText;

class NotedTextMaker{
    public function makeNoted($user, $text, $type){
        $noted = new NotedText();
        $noted->setUser($user)
                ->setType($type)
                ->setText($text);
        return $noted;
    }
}
