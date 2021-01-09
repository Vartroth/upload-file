<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Exception\UploadFileException;

class LocalFileSystem implements StorageSystemInterface
{

    public function process(FileType $file, string $path = __DIR__ . "../../"): FileType
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException($file->getLang()->write($file->getLang()::PATH_NOT_EXIST));
        }
        move_uploaded_file(
            (string) $file->toArray()['tmp_name'],
            $path . (string) $file->toArray()['name']
        );

        if (!is_file($path . (string) $file->toArray()['name'])) {
            throw new UploadFileException($file->getLang()->write($file->getLang()::UPLOAD_ERROR));
        }

        return $file;
    }
}
