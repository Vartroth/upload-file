<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImagePng implements ImageMimeType
{

    private $widht;
    private $height;
    private $quality;

    public function __construct(int $widht, int $height, int $quality)
    {
        $this->widht = $widht;
        $this->height = $height;
        $this->quality = $quality;
    }

    public function resize(Image $image): Image
    {
        if ($image->getWidth() > $this->widht) {
            $origin = @imagecreatefrompng($image->getTmpName());
            $thumb = imagecreatetruecolor($this->widht, $this->height);
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->widht, $this->height, $image->getWidth(), $image->getHeight());
            imagepng($thumb, $image->getTmpName(), (int) (($this->quality / 10) - 1));
            $image->setWidth = $this->widht;
            $image->setHeight = $this->height;
        }
        return $image;
    }

}
