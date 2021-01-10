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
        if (!is_dir($path)) {
            throw new InvalidArgumentException();
        }
        $this->path = $path;
    }

    public function process(FileType $file): FileType
    {

        $ext = \explode('.', $file->getName());
        $file_name = str_replace("." . $ext[(sizeof($ext) - 1)], "", $file->getName());

        $file->setName(SanitizerStringFile::exec($file_name) . "." . $ext[(sizeof($ext) - 1)]);

        @move_uploaded_file(
            (string) $file->toArray()['tmp_name'],
            $this->path . (string) $file->getName()
        );

        if (!is_file($this->path . (string) $file->getName())) {
            throw new UploadFileException($file->getLang()->write($file->getLang()::UPLOAD_ERROR));
        }

        return $file;
    }
}
