<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\Types;

use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Exception\UploadFileException;
use Vartroth\UploadFile\Language\LangString;

class File implements FileType
{

    /**
     * The file name
     *
     * @var string
     */
    private $name;

    /**
     * Temp upload File
     *
     * @var strint
     */
    private $tmp_name;

    /**
     * File type type (ej: image/png)
     *
     * @var string
     */
    private $type;

    /**
     * File size
     *
     * @var int
     */
    private $size;

    /**
     * Language Strings class
     *
     * @var LangString
     */
    private $lang;

    /**
     * __construct
     *
     * @param array $FileData
     * @param LangString $lang
     */
    public function __construct(array $FileData, LangString $lang)
    {
        $this->lang = $lang;

        if (!isset($FileData) || !isset($FileData['error'])) {
            throw new InvalidArgumentException($this->lang->write($this->lang::UPLOAD_ERROR));
        }

        if ($FileData['error'] || !is_file($FileData['tmp_name'])) {
            throw new UploadFileException($this->lang->write($this->lang::UPLOAD_ERROR));
        }

        $this->name = $FileData['name'];
        $this->size = $FileData['size'];
        $this->type = $FileData['type'];
        $this->tmp_name = $FileData['tmp_name'];

        return $this;
    }

    /**
     * validateType
     *
     * @param array $typeList
     *
     * @return FileType
     */
    public function validateType(array $typeList = ['application/pdf']): self
    {
        $valid = false;
        foreach ($typeList as $type) {
            $valid = ($type == $this->type) ? true : $valid;
        }

        if (!$valid) {
            throw new UploadFileException($this->lang->write($this->lang::MIME_TYPE));
        }
        return $this;
    }

    /**
     * Set file name
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the file name
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get file mime type (ej: image/png)
     *
     * @return  string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get $size
     *
     * @return  int
     */
    public function getSize(): int
    {
        return (int) $this->size;
    }

    /**
     * Get language Strings class
     *
     * @return  LangString
     */
    public function getLang(): LangString
    {
        return $this->lang;
    }

    /**
     * Get file data in array format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'tmp_name' => $this->tmp_name,
        ];
    }

    /**
     * Get file data in Json format
     *
     * @return string
     */
    public function toJson(): string
    {
        return \json_encode($this->toArray());
    }
}
