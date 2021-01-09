<?php
declare (strict_types = 1);

namespace Vartroth\UploadFile;

use Vartroth\UploadFile\Entity\File;
use Vartroth\UploadFile\Entity\UploadedFileType;
use Vartroth\UploadFile\Storage\StorageSystemInterface;

class Upload
{
    private $file;
    private $storageSystem;
    private $path;

    public function __construct(
        UploadedFileType $uploadedfile,
        StorageSystemInterface $storageSystem,
        string $path
    ) {
        $this->file = $uploadedfile->load();
        $this->storageSystem = $storageSystem;
        $this->path = $path;
    }

    public function save(): File
    {
        return $result = $this->storageSystem->process($this->file, $this->path);
    }
}
