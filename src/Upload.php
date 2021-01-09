<?php
declare (strict_types = 1);

namespace Vartroth\UploadFile;

use Vartroth\UploadFile\Entity\File;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Storage\StorageSystemInterface;

class Upload
{
    private $file;
    private $storageSystem;
    private $path;

    public function __construct(
        FileType $uploadedfile,
        StorageSystemInterface $storageSystem,
        string $path
    ) {
        $this->file = $uploadedfile;
        $this->storageSystem = $storageSystem;
        $this->path = $path;
    }

    public function save(): FileType
    {
        return $this->storageSystem->process($this->file, $this->path);
    }
}
