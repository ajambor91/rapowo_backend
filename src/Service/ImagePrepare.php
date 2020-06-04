<?php
namespace App\Service;

use App\Entity\Image;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ImagePrepare{
    private $avatar;
    private $size;
    private $moved;
    private $extension;
    private $user;
    private $images = [];
    private $em;
    private $scaleImages = [];
    private $scaleSizes = [];
    private $croppedMainImage;
    private $mainType;
    private $avatarResource;
    private $resizedAvatar;
    const SAVE_PATH = 'images/avatars/';
    const ALLOWED_EXTENSIONS = ['jpeg','jpg','gif','png'];
    public function __construct($avatar, User $user, EntityManagerInterface $entityManager, $scaleSizes, $mainType)
    {
        $this->user = $user;
        $this->avatar = $avatar['path'];
        $this->size = $avatar['size'];
        $this->moved = $avatar['moved'];
        $this->em = $entityManager;
        $this->scaleSizes = $scaleSizes;
        $this->mainType = $mainType;
    }
    private function getExtensions(){
        $extension = $imageInfo = getimagesizefromstring($this->avatar);
        $this->extension = str_replace('image/','',$extension['mime']);
    }
    private function decodeBase(){
        $this->avatar = explode(',', $this->avatar);
        $this->avatar = base64_decode($this->avatar[1]);
    }
    private function checkTheExtensionAllowed(){
        if(in_array($this->extension, self::ALLOWED_EXTENSIONS)){
            return true;
        }
        return false;
    }
    private function selectSaveMethod(){
        switch ($this->extension){
            case 'jpeg' || 'jpg':
                return $this->saveToJpg();
            case 'gif':
                return $this->saveToGif();
            case 'png':
                return $this->saveToPng();
        }
    }
    private function makePath($imageType){
        return self::SAVE_PATH.mb_strtolower($this->user->getNick()).'_'.Image::IMAGE_TYPES[$imageType].'.'.$this->extension;
    }
    private function saveToJpg( ){
        $i = 0;
        foreach ($this->scaleImages as $key => $image ){
            $path = $this->makePath($key);
            if(!imagejpeg($image,$path)){
                return false;
            }
            $this->images[$i]['path'] = $path;
            $this->images[$i]['type'] = $key;
            $i++;
        }
        return true;
    }
    private function saveToGif(){
        $i = 0;
        foreach ($this->scaleImages as $key => $image){
            $path = $this->makePath($key);
            if(!imagegif($image, $key)){
                return false;
            }
            $this->images[$i]['path'] = $path;
            $this->images[$i]['type'] = $key;
            $i++;
        }
        return true;
    }
    private function saveToPng(){
        $i = 0;
        foreach ($this->scaleImages as $key => $image){
            $path = $this->makePath($key);
            if(!imagepng($image, $key)){
                return false;
            }
            $this->images[$i]['path'] = $path;
            $this->images[$i]['type'] = $key;
        }
        return true;
    }
    private function saveImagePathToDatabase(){
        foreach ($this->images as $image){
            $avatar = new Image();
            $avatar->setPath($image['path'])
                    ->setType($image['type'])
                    ->setUser($this->user);
            $this->user->addImage($avatar);
        }
    }
    private function createDir(){
        if(!file_exists(self::SAVE_PATH) || !is_dir(self::SAVE_PATH)){
            mkdir(self::SAVE_PATH,0777, true);
        }
    }
    private function scale(){
        foreach ($this->scaleSizes as $size){
            $this->scaleImages[$size['type']] = imagescale($this->croppedMainImage, $size['width'], $size['height']);
        }
        $this->scaleImages[$this->mainType] = $this->avatarResource;
    }
    private function save(){

    }
    public function prepareImage(){
        $this->decodeBase();
        $this->getExtensions();
        if(!$this->checkTheExtensionAllowed()){
            return $this->extension;
        }
        $this->avatarResource = imagecreatefromstring($this->avatar);
        $this->resizedAvatar = imagescale($this->avatarResource, $this->size['sizeX'], $this->size['sizeY']);
        $this->croppedMainImage = imagecrop($this->resizedAvatar, ['x'=>$this->moved['moveX'], 'y'=>$this->moved['moveY'],'width'=> Image::MAIN_SIZES[$this->mainType]['sizeX'],'height' => Image::MAIN_SIZES[$this->mainType]['sizeY']]);
        $this->scale();
        $this->createDir();
        $imageSave = ($this->selectSaveMethod());
        if(!$imageSave){
            return;
        }
        $this->saveImagePathToDatabase();
        return $this->user;
    }

}
