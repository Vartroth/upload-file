<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

use Vartroth\UploadFile\Entity\Types\Image;

interface ImageMimeType
{

    public function __construct(float $widht, float $height, float $quality);

    public function resize(Image $image): Image;
}
