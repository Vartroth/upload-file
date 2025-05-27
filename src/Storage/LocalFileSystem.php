<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Storage;

use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Exception\UploadFileException;

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
        if (! is_dir($path)) {
            throw new InvalidArgumentException();
        }
        $this->path = $path;
    }

    /**
     * process
     *
     * @param FileType $file
     *
     * @return FileType
     */
    public function process(FileType $file): FileType
    {

        $ext = \explode('.', $file->getName());

        $file->setName(
            ($file->getKeepName()) ? $file->getName() : uniqid() . "." . $ext[(sizeof($ext) - 1)]
        );

        $this->moveUploadedFile(
            (string) $file->toArray()['tmp_name'],
            $this->path . (string) $file->getName()
        );

        if (! is_file($this->path . (string) $file->getName())) {
            throw new UploadFileException($file->getLang()->write($file->getLang()::UPLOAD_ERROR));
        }

        return $file;
    }

    /**
     * moveUploadedFile is a function for Unit Test becouse there are
     * a hard dependecy with 'move_uploaded_file'
     *
     * @param string $origin
     * @param string $destination
     *
     * @return bool
     */
    private function moveUploadedFile(string $origin, string $destination): bool
    {
        return move_uploaded_file($origin, $destination);
    }
}
