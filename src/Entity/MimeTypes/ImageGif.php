<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImageGif implements ImageMimeType
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
            $origin = @imagecreatefrompng($image->getTmpName());
            $thumb  = imagecreatetruecolor($this->width, $this->height);
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            imagecopyresampled($thumb, $origin, 0, 0, 0, 0, $this->width, $this->height, $image->getWidth(), $image->getHeight());
            imagegif($thumb, $image->getTmpName(), (int) (($this->quality / 10) - 1));
            $image->setWidth($this->width);
            $image->setHeight($this->height);
        }
        return $image;
    }

}
