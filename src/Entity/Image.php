<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImageRepository")
 */
class Image
{
    const TYPE_MAIN = 1;
    const TYPE_NAVBAR_THUMB = 2;
    const TYPE_AUTHOR_THUMB = 3;
    const TYPE_ORIGINAL = 4;
    const TYPE_BACKGROUND_ORIGINAL = 5;
    const TYPE_BACKGROUND_CROPPED = 6;
    const TYPE_BACKGROUND_SONG = 7;
    const IMAGE_TYPES = [
        self::TYPE_MAIN => 'main',
        self::TYPE_NAVBAR_THUMB => 'navbar_thumb',
        self::TYPE_AUTHOR_THUMB => 'author_thumb',
        self::TYPE_ORIGINAL => 'original',
        self::TYPE_BACKGROUND_ORIGINAL => 'background_original',
        self::TYPE_BACKGROUND_CROPPED => 'background_cropped',
        self::TYPE_BACKGROUND_SONG => 'background_song'
    ];
    const SIZES = [
        self::TYPE_ORIGINAL => [
            0 => [
                'width' => 142,
                'height' => 142,
                'type' => self::TYPE_AUTHOR_THUMB
            ],
            1 => [
                'width' => 35,
                'height' => 35,
                'type' => self::TYPE_NAVBAR_THUMB
            ],
            2 => [
                'width' => 175,
                'height' => 175,
                'type' => self::TYPE_MAIN
            ]
        ],
        self::TYPE_BACKGROUND_ORIGINAL => [
            0 => [
                'width' => 1140,
                'height' => 310,
                'type' => self::TYPE_BACKGROUND_CROPPED
            ],
            1 => [
                'width' => 925,
                'height' => 255,
                'type' => self::TYPE_BACKGROUND_SONG
            ]
        ],
    ];

    const MAIN_SIZES = [
        self::TYPE_ORIGINAL => [
            'sizeX' => 175,
            'sizeY' => 175
        ],
        self::TYPE_BACKGROUND_ORIGINAL => [
            'sizeX' => 1100,
            'sizeY' => 300
        ]
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="images", cascade={"persist"})
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }


}
