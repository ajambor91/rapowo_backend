<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AgreementRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Agreement
{
    const TYPE_ACCEPT_RULE = 1;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;



    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="agreements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\Column(type="string", length=255)
     */
    private $ipAddr;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $localization;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getIpAddr(): ?string
    {
        return $this->ipAddr;
    }

    public function setIpAddr(string $ipAddr): self
    {
        $this->ipAddr = $ipAddr;

        return $this;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    public function setLocalization(string $localization): self
    {
        $this->localization = $localization;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate(){
        $this->updatedAt = new \DateTime();
        $this->setUserData();
    }
    /**
     * @ORM\PrePersist()
     */
    public function prePersist(){
        $this->updatedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->isActive = true;
        $this->setUserData();
    }
    public function softDelete(){
        $this->isActive = false;
        $this->setUserData();
    }
    private function setUserData(){
        $request = Request::createFromGlobals();
        $this->ipAddr = $request->getClientIp() ?: '0.0.0.0';
        $this->localization = $request->getLocale();
    }
}
