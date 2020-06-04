<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Event
{
    const SKIP_EVENTS = 40;

    const NEW_TEXT = 1;
    const POPULAR_TEXT = 2;
    const MOST_COMMENTED = 3;
    const NEW_FOLLOWED = 4;
    const POPULAR_FOLLOWED = 5;
    const NEW_COMMENT_FOR_USER = 6;
    const FOLlOWED_TEXT_COMMENT = 7; //TODO
    const REPLY_COMMENT = 8;
    const MAIN_MAILING_TYPES = [
        self::NEW_TEXT => 'new-text',
        self::POPULAR_TEXT => 'popular-text',
        self::MOST_COMMENTED => 'most-comment',
        self::NEW_FOLLOWED => 'new-folllowed-text',
        self::POPULAR_FOLLOWED => 'popular-followed',
        self::NEW_COMMENT_FOR_USER => 'new-comment',
        self::REPLY_COMMENT => 'reply-comment'
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="events")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Text", inversedBy="events")
     * @ORM\JoinColumn(nullable=false)
     */
    private $text;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Error", mappedBy="event")
     */
    private $errors;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isRead;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Comment", cascade={"persist", "remove"})
     */
    private $comment;

    public function __construct()
    {
        $this->errors = new ArrayCollection();
    }

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

    public function getText(): ?Text
    {
        return $this->text;
    }

    public function setText(?Text $text): self
    {
        $this->text = $text;

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

    /**
     * @return Collection|Error[]
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    public function addError(Error $error): self
    {
        if (!$this->errors->contains($error)) {
            $this->errors[] = $error;
            $error->setEvent($this);
        }

        return $this;
    }

    public function removeError(Error $error): self
    {
        if ($this->errors->contains($error)) {
            $this->errors->removeElement($error);
            // set the owning side to null (unless already changed)
            if ($error->getEvent() === $this) {
                $error->setEvent(null);
            }
        }

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist(): void {
        $this->isRead = false;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
