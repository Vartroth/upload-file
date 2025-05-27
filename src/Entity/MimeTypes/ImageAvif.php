<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\MimeTypes;

use Vartroth\UploadFile\Entity\ImageMimeType;
use Vartroth\UploadFile\Entity\Types\Image;

class ImageAvif implements ImageMimeType
{

    public function __construct(
        private int $width,
        private int $height,
        private int $quality
    ) {
        if (! function_exists('imagecreatefromavif') || ! function_exists('imageavif')) {
            throw new \RuntimeException('AVIF support is not available in this PHP installation');
        }
    }

    public function resize(Image $image): Image
    {
        if ($image->getWidth() > $this->width) {
            $origin = @imagecreatefromavif($image->getTmpName());

            if ($origin === false) {
                throw new \RuntimeException('Failed to create image from AVIF file');
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

            $result = imageavif($thumb, $image->getTmpName(), $this->quality);

            if ($result === false) {
                throw new \RuntimeException('Failed to save AVIF image');
            }

            imagedestroy($origin);
            imagedestroy($thumb);

            $image->setWidth($this->width);
            $image->setHeight($this->height);
        }

        return $image;
    }

    /**
     * Check if AVIF support is available
     */
    public static function isSupported(): bool
    {
        return function_exists('imagecreatefromavif') && function_exists('imageavif');
    }
}
