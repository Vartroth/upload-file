<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImageWebp implements ImageMimeType
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
            $origin = @imagecreatefromwebp($image->getTmpName());

            if ($origin === false) {
                throw new \RuntimeException('Failed to create image from WebP file');
            }

            $thumb = imagecreatetruecolor($this->width, $this->height);

            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefill($thumb, 0, 0, $transparent);
            imagealphablending($thumb, true);

            imagecopyresampled(
                $thumb,
                $origin,
                0, 0, 0, 0,
                $this->width,
                $this->height,
                $image->getWidth(),
                $image->getHeight()
            );
            imagewebp($thumb, $image->getTmpName(), $this->quality);

            imagedestroy($origin);
            imagedestroy($thumb);

            $image->setWidth($this->width);
            $image->setHeight($this->height);
        }

        return $image;
    }
}
