<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\Types\Image;
use Vartroth\UploadFile\Entity\ImageMimeType;

class ImageJpg implements ImageMimeType
{

    public function __construct(
        private int $width,
        private int $height,
        private int $quality
    ) {
    }

    public function resize(Image $image): Image
    {
        if ($image->getWidth() > $this->width) {
            $origin = @imagecreatefromjpeg($image->getTmpName());
            $thumb  = imagecreatetruecolor($this->width, $this->height);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->width, $this->height, $image->getWidth(), $image->getHeight());
            imagejpeg($thumb, $image->getTmpName(), $this->quality);
            $image->setWidth($this->width);
            $image->setHeight($this->height);
        }
        return $image;
    }

}
