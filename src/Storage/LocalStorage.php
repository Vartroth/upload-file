<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

class LocalFileSystem implements StorageSystemInterface
{

    public function process(UploadedFileType $file, string $path = __DIR__ . "../../"): File
    {
        return false;
    }
}
