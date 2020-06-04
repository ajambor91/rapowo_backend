<?php
namespace App\Service;

use App\Entity\Event;
use App\Entity\Text;
use App\Repository\LangRepository;

class TextMaker{
    private $lang;
    public function __construct(LangRepository $langRepository)
    {
        $this->lang = $langRepository;
    }

    public function createText($data, $user, $draft = Text::NON_DRAFT){
        $lang = $this->lang->findOneBy(['langCode'=>$data['lang']]);
        $text = new Text();
        $text->setTitle($data['title'])
            ->setUser($user)
            ->setContent($data['content'])
            ->setSlug($data['slug'])
            ->setLang($lang)
            ->setDraft($draft);
        return $text;
    }
}
