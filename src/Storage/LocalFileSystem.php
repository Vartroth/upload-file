<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Exception\UploadFileException;
use Vartroth\Utils\DataConversion\Strings\SanitizerStringFile;

class LocalFileSystem implements StorageSystemInterface
{

    private $path;

    /**
     * Construct
     *
     * @param $path string
     */
    public function __construct(string $path = __DIR__ . "../../")
    {
        $this->path = $path;
    }

    public function process(FileType $file): FileType
    {

        $file->setName(SanitizerStringFile::exec((string) $file->toArray()['name']));

        if (!is_dir($this->path)) {
            throw new InvalidArgumentException($file->getLang()->write($file->getLang()::PATH_NOT_EXIST));
        }

        move_uploaded_file(
            (string) $file->toArray()['tmp_name'],
            $this->path . (string) $file->toArray()['name']
        );

        if (!is_file($this->path . (string) $file->toArray()['name'])) {
            throw new UploadFileException($file->getLang()->write($file->getLang()::UPLOAD_ERROR));
        }

        return $file;
    }
}
