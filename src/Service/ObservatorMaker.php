<?php
namespace App\Service;
use App\Entity\Observator;

class ObservatorMaker{
    public function createObservator($user,$currentUser){
        $observator = new Observator();
        $observator->setUser($user)
                    ->setObservator($currentUser);
        return $observator;
    }
}
