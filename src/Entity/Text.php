<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TextRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Text
{
    const POPULAR_LIMIT = 2;
    const MAX_TEXTS = 5;


    const DRAFT = true;
    const NON_DRAFT = false;
    const DRAFT_PAYLOAD = 'draft';
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=8096)
     */
    private $content;

    /**
     * @ORM\Column(type="integer")
     */
    private $note;


    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="texts")
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="text", cascade={"all"}, orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NotedText", mappedBy="text", cascade={"all"}, orphanRemoval=true)
     */
    private $notedTexts;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Setting", mappedBy="text", orphanRemoval=true)
     */
    private $settings;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Event", mappedBy="text", cascade={"all"}, orphanRemoval=true)
     */
    private $events;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lang", inversedBy="texts")
     */
    private $lang;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="boolean")
     */
    private $draft;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $softDelete;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isAccepted;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->notedTexts = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setText($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getText() === $this) {
                $comment->setText(null);
            }
        }

        return $this;
    }
    /**
     * @ORM\PrePersist()
     */
    public function prePersists(){
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->note = 0;
        $this->isAccepted = false;
    }
    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate(){
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return Collection|NotedText[]
     */
    public function getNotedTexts(): Collection
    {
        return $this->notedTexts;
    }

    public function addNotedText(NotedText $notedText): self
    {
        if (!$this->notedTexts->contains($notedText)) {
            $this->notedTexts[] = $notedText;
            $notedText->setText($this);
        }

        return $this;
    }

    public function removeNotedText(NotedText $notedText): self
    {
        if ($this->notedTexts->contains($notedText)) {
            $this->notedTexts->removeElement($notedText);
            // set the owning side to null (unless already changed)
            if ($notedText->getText() === $this) {
                $notedText->setText(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Setting[]
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function addSetting(Setting $setting): self
    {
        if (!$this->settings->contains($setting)) {
            $this->settings[] = $setting;
            $setting->setText($this);
        }

        return $this;
    }

    public function removeSetting(Setting $setting): self
    {
        if ($this->settings->contains($setting)) {
            $this->settings->removeElement($setting);
            // set the owning side to null (unless already changed)
            if ($setting->getText() === $this) {
                $setting->setText(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setText($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->contains($event)) {
            $this->events->removeElement($event);
            // set the owning side to null (unless already changed)
            if ($event->getText() === $this) {
                $event->setText(null);
            }
        }

        return $this;
    }

    public function getLang(): ?Lang
    {
        return $this->lang;
    }

    public function setLang(?Lang $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDraft(): ?bool
    {
        return $this->draft;
    }

    public function setDraft(bool $draft): self
    {
        $this->draft = $draft;

        return $this;
    }

    public function getSoftDelete(): ?\DateTimeInterface
    {
        return $this->softDelete;
    }

    public function setSoftDelete(?\DateTimeInterface $softDelete): self
    {
        $this->softDelete = $softDelete;

        return $this;
    }

    public function getIsAccepted(): ?bool
    {
        return $this->isAccepted;
    }

    public function setIsAccepted(bool $isAccepted): self
    {
        $this->isAccepted = $isAccepted;

        return $this;
    }
}
