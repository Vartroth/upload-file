<?php
declare (strict_types = 1);

namespace Vartroth\UploadFile;

use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Storage\StorageSystemInterface;

class Upload
{
    private $file;
    private $storageSystem;

    public function __construct(
        FileType $filetype,
        StorageSystemInterface $storageSystem
    ) {
        $this->file          = $filetype;
        $this->storageSystem = $storageSystem;
    }

    public function save(): FileType
    {
        return $this->storageSystem->process($this->file);
    }
}
