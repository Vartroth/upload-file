<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use Vartroth\UploadFile\Entity\File;
use Vartroth\UploadFile\Entity\UploadedFileType;

interface StorageSystemInterface
{
    public function process(UploadedFileType $file, string $path): File;
}
