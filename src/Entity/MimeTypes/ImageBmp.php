<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImageBmp implements ImageMimeType
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
            $origin = imagecreatefrombmp($image->getTmpName());
            $thumb = imagecreatetruecolor($this->widht, $this->height);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->widht, $this->height, $image->getWidth(), $image->getHeight());
            imagebmp($thumb, $image->getTmpName(), $this->quality);
            $image->setWidth = $this->widht;
            $image->setHeight = $this->height;
        }
        return $image;
    }

}
