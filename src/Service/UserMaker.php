<?php

namespace App\Service;

use App\Entity\Agreement;
use App\Entity\Lang;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\LangRepository;

class UserMaker
{
    private $lang;
    public function __construct(LangRepository $langRepository)
    {
        $this->lang = $langRepository;
    }

    public function createSocialUser($data){
        $user = new User();
        $user->setEmail($data['email'])
            ->setIsActive(true)
            ->setName($data['name']);
        if($data['type'] === User::SOCIAL_TYPE_GOOGLE){
            $user->setGoogleId($data['socialId']);
        }elseif ($data['type'] === User::SOCIAL_TYPE_FACEBOOK){
            $user->setFbId($data['socialId']);
        }
        $lang = $this->lang->findOneBy(['langCode' => $data['lang']]);
        $user->setLang($lang);
        return $user;
    }

    public function createUser($data)
    {
        $password = function ($passwords) {
            if ($passwords['password'] === $passwords['repeatPassword']) {
                return password_hash($passwords['password'], PASSWORD_DEFAULT);
            }
            return false;
        };
        if (!$password($data['passwords'])) {
            return false;
        }

        $user = new User();
        $user->setEmail($data['email'])
            ->setNick($data['nick'])
            ->setPassword($password($data['passwords']))
            ->setHash(sha1($data['nick'] . time()));
        $ruleAgreement = new Agreement();
        $ruleAgreement->setType(Agreement::TYPE_ACCEPT_RULE);
        $user->addAgreement($ruleAgreement);
        if(isset($data['agreement']) && $data['agreement'] === true){
            $setting = new SettingFactory();
            foreach (Setting::MAIN_MAILING_TYPES as $key => $type){
                $user->addSetting($setting->createSetting($user, $key));
            }
        }

        if (isset($data['city']) && !empty($data['city'])) {
            $user->setCity($data['city']);
        }
        if (isset($data['sex']) && !empty($data['sex'])) {
            $user->setSex($data['sex']);
        }
        if (isset($data['name']) && !empty($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['birthdate']) && !empty($data['birthdate'])) {
            $user->setBirthdate(new \DateTime(implode('-', $data['birthdate'])));
        }
        $lang = $this->lang->findOneBy(['langCode' => $data['lang']]);
        $user->setLang($lang);
        return $user;
    }

}
