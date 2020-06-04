<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SettingRepository")
 */
class Setting
{
    const NEW_TEXT = 1;
    const POPULAR_TEXT = 2;
    const MOST_COMMENTED = 3;
    const NEW_FOLLOWED = 4;
    const POPULAR_FOLLOWED = 5;
    const NEW_COMMENT_FOR_USER = 6;
    const REPLY_COMMENT = 7;
    const MAIN_MAILING_TYPES = [
        self::NEW_TEXT => 'nowy tekst',
        self::POPULAR_TEXT => 'popularny tekst',
        self::MOST_COMMENTED => 'najwięcej komentarzy',
        self::NEW_FOLLOWED => 'nowy tekst obserwowanych',
        self::POPULAR_FOLLOWED => 'popularny tekst obserwowanych',
        self::NEW_COMMENT_FOR_USER => 'nowy komentarz',
        self::REPLY_COMMENT => 'odpowiedź do komentarza'
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="settings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Text", inversedBy="settings")
     */
    private $text;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): ?Text
    {
        return $this->text;
    }

    public function setText(?Text $text): self
    {
        $this->text = $text;

        return $this;
    }

}
