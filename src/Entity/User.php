<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    const ROLE_ADMIN = ['ROLE_ADMIN'];

    const SOCIAL_TYPE_GOOGLE = 'GOOGLE';
    const SOCIAL_TYPE_FACEBOOK = 'FACEBOOK';
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;
    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string" , nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hash;

    /**
     * @ORM\Column(type="boolean",nullable=true)
     */
    private $isActive;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sex;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;





    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Text", mappedBy="user")
     */
    private $texts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="user")
     */
    private $comments;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NotedText", mappedBy="user")
     */
    private $notedTexts;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     */
    private $nick;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $birthdate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Image", mappedBy="user", cascade={"persist"},orphanRemoval=true)
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Agreement", mappedBy="user" ,orphanRemoval=true, cascade={"persist"})
     */
    private $agreements;



    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Setting", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    private $settings;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Event", mappedBy="user", cascade={"persist"})
     */
    private $events;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $googleId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fbId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $softdeleta;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastLoginDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Lang", inversedBy="users")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lang;


    public function __construct()
    {
        $this->texts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->notedTexts = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->agreements = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->events = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        //$roles = ['ROLE_USER'];

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash($hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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

    public function getSex(): ?string 
    {
        return $this->sex;
    }

    public function setSex(string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

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
            $text->setUser($this);
        }

        return $this;
    }

    public function removeText(Text $text): self
    {
        if ($this->texts->contains($text)) {
            $this->texts->removeElement($text);
            // set the owning side to null (unless already changed)
            if ($text->getUser() === $this) {
                $text->setUser(null);
            }
        }

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
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }
    /**
     * @ORM\PrePersist()
     */
    public function prePersist(){
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->isActive = $this->isActive ?? false;
        $this->roles === null || empty($this->roles) ? ['ROLE_USER'] : $this->roles ;
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
            $notedText->setUser($this);
        }

        return $this;
    }

    public function removeNotedText(NotedText $notedText): self
    {
        if ($this->notedTexts->contains($notedText)) {
            $this->notedTexts->removeElement($notedText);
            // set the owning side to null (unless already changed)
            if ($notedText->getUser() === $this) {
                $notedText->setUser(null);
            }
        }

        return $this;
    }

    public function getNick(): ?string
    {
        return $this->nick;
    }

    public function setNick(string $nick): self
    {
        $this->nick = $nick;

        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * @return Collection|Image[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setUser($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
            // set the owning side to null (unless already changed)
            if ($image->getUser() === $this) {
                $image->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Agreement[]
     */
    public function getAgreements(): Collection
    {
        return $this->agreements;
    }

    public function addAgreement(Agreement $agreement): self
    {
        if (!$this->agreements->contains($agreement)) {
            $this->agreements[] = $agreement;
            $agreement->setUser($this);
        }

        return $this;
    }

    public function removeAgreement(Agreement $agreement): self
    {
        if ($this->agreements->contains($agreement)) {
            $this->agreements->removeElement($agreement);
            // set the owning side to null (unless already changed)
            if ($agreement->getUser() === $this) {
                $agreement->setUser(null);
            }
        }

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
            $setting->setUser($this);
        }

        return $this;
    }

    public function removeSetting(Setting $setting): self
    {
        if ($this->settings->contains($setting)) {
            $this->settings->removeElement($setting);
            // set the owning side to null (unless already changed)
            if ($setting->getUser() === $this) {
                $setting->setUser(null);
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
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->contains($event)) {
            $this->events->removeElement($event);
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getFbId(): ?string
    {
        return $this->fbId;
    }

    public function setFbId(?string $fbId): self
    {
        $this->fbId = $fbId;

        return $this;
    }

    public function getSoftdeleta(): ?\DateTimeInterface
    {
        return $this->softdeleta;
    }

    public function setSoftdeleta(?\DateTimeInterface $softdeleta): self
    {
        $this->softdeleta = $softdeleta;

        return $this;
    }

    public function getLastLoginDate(): ?\DateTimeInterface
    {
        return $this->lastLoginDate;
    }

    public function setLastLoginDate(?\DateTimeInterface $lastLoginDate): self
    {
        $this->lastLoginDate = $lastLoginDate;

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
    
}
