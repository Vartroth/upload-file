<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImagePng implements ImageMimeType
{

    private $widht;
    private $height;
    private $quality;

    public function __construct(float $widht, float $height, float $quality)
    {
        $this->widht = $widht;
        $this->height = $height;
        $this->quality = $quality;
    }

    public function resize(Image $image): Image
    {
        if ($image->getWidth() > $this->widht) {
            $origin = @imagecreatefromjpeg($image->getTmpName());
            $thumb = imagecreatetruecolor($this->widht, $this->height);
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->widht, $this->height, $image->getWidth(), $image->getHeight());
            imagepng($thumb, $image->getTmpName(), null, $this->quality);
            $image->setWidth = $this->widht;
            $image->setHeight = $this->height;
        }
        return $image;
    }

}
