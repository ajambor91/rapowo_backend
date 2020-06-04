<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LangRepository")
 */
class Lang
{
    const LANG_PL = 'pl';
    const LANG_EN = 'en';
    const LANGS_FULLNAMES = [
        self::LANG_PL => 'polish',
        self::LANG_EN => 'english'
    ];
    const SPECIAL_CHARS = [
        self::LANG_PL => ['ą','ć','ę','ł','ń','ó','ź','ż']
    ];
    const ALLOWED_CHARS = [
        'a','c','e','l','n','o','z','z'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $langCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fullName;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="lang")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Text", mappedBy="lang")
     */
    private $texts;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->texts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLangCode(): ?string
    {
        return $this->langCode;
    }

    public function setLangCode(string $langCode): self
    {
        $this->langCode = $langCode;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setLang($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getLang() === $this) {
                $user->setLang(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Text[]
     */
    public function getTexts(): Collection
    {
        return $this->texts;
    }

    public function addText(Text $text): self
    {
        if (!$this->texts->contains($text)) {
            $this->texts[] = $text;
            $text->setLang($this);
        }

        return $this;
    }

    public function removeText(Text $text): self
    {
        if ($this->texts->contains($text)) {
            $this->texts->removeElement($text);
            // set the owning side to null (unless already changed)
            if ($text->getLang() === $this) {
                $text->setLang(null);
            }
        }

        return $this;
    }
}
