<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NotedTextRepository")
 */
class NotedText
{
    const LIKE_TEXT = 'like';
    const DISLIKE_TEXT = 'dislike';
    const LIKES_TYPES = [
        self::LIKE_TEXT => 1,
        self::DISLIKE_TEXT =>0
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Text", inversedBy="notedTexts", cascade={"persist"})
     */
    private $text;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="notedTexts")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?bool
    {
        return $this->type;
    }

    public function setType(bool $type): self
    {
        $this->type = $type;

        return $this;
    }
}
