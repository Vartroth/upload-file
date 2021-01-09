<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use Vartroth\UploadFile\Entity\FileType;

class LocalFileSystem implements StorageSystemInterface
{

    public function process(FileType $file, string $path = __DIR__ . "../../"): FileType
    {
        return $file;
    }
}
