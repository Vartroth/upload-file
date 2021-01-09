<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use Vartroth\UploadFile\Entity\FileType;

interface StorageSystemInterface
{
    public function process(FileType $file, string $path): FileType;
}
