<?php
namespace App\Service;

use App\Entity\Setting;
use App\Entity\User;

class SettingFactory{
    public function createSetting(User $user, int $type){
        $setting = new Setting();
        $setting->setUser($user)
                ->setType($type);
        return $setting;
    }
}
