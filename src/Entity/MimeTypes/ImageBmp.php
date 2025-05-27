<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImageBmp implements ImageMimeType
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
            $origin = imagecreatefrombmp($image->getTmpName());
            $thumb  = imagecreatetruecolor($this->width, $this->height);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->width, $this->height, $image->getWidth(), $image->getHeight());
            imagebmp($thumb, $image->getTmpName());
            $image->setWidth($this->width);
            $image->setHeight($this->height);
        }
        return $image;
    }

}
