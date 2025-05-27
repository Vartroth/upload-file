<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

use Vartroth\UploadFile\Entity\Types\Image;

interface ImageMimeType
{

    public function __construct(int $width, int $height, int $quality);

    public function resize(Image $image): Image;
}
