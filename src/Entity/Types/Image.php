<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\Types;

use Exception;
use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Entity\GdVersion;
use Vartroth\UploadFile\Entity\MimeTypes\ImageBmp;
use Vartroth\UploadFile\Entity\MimeTypes\ImageJpg;
use Vartroth\UploadFile\Entity\MimeTypes\ImagePng;
use Vartroth\UploadFile\Exception\UploadFileException;
use Vartroth\UploadFile\Language\LangString;

class Image implements FileType
{

    const IMAGE_SIZE_WIDTH = 0;
    const IMAGE_SIZE_HEIGHT = 1;
    const DEFAULT_QUALITY = 100;
    const VALID_MIME_TYPES = [
        'image/jpg',
        'image/jpeg',
        'image/pjpeg',
        'image/bmp',
        'image/png',
    ];

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
     * Define if web keep the name or generate a unique name with unique_id function
     *
     * @var bool
     */
    private $keep_name;

    /**
     * The image width in px
     *
     * @var int
     */
    private $width;

    /**
     * The image height in px
     *
     * @var int
     */
    private $height;

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

        $info = \getimagesize($FileData['tmp_name']);

        $this->name = $FileData['name'];
        $this->size = $FileData['size'];
        $this->type = $FileData['type'];
        $this->tmp_name = $FileData['tmp_name'];
        $this->keep_name = false;
        $this->width = (int) $info[self::IMAGE_SIZE_WIDTH];
        $this->height = (int) $info[self::IMAGE_SIZE_HEIGHT];

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
     * Get define if web keep the name or generate a unique name with unique_id function
     *
     * @return  bool
     */
    public function getKeepName(): bool
    {
        return $this->keep_name;
    }

    /**
     * Get the image width in px
     *
     * @return  int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Get the image height in px
     *
     * @return  height
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Get temp upload File
     *
     * @return  strint
     */
    public function getTmpName()
    {
        return $this->tmp_name;
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
            'widht' => $this->width,
            'height' => $this->height,
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

    /**
     * validateType
     *
     * @param array $typeList
     *
     * @return FileType
     */
    public function validateType(array $typeList = self::VALID_MIME_TYPES): self
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
     * resizeImage
     *
     * @param int $width
     * @param int $height
     *
     * @return self
     */
    public function resizeImage(int $width, int $height = 0): self
    {
        $image = $this;

        $this->validateType();

        if (!(new GdVersion())()) {
            throw new Exception("Gd Version not Found");
        }

        $new_height = ((bool) $height) ? $height : ($this->height / ($this->width / $width));

        switch ($this->type) {
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/pjpeg':
                $image = (new ImageJpg((int) $width, (int) $new_height, self::DEFAULT_QUALITY))->resize($image);
                break;
            case 'image/png':
                $image = (new ImagePng((int) $width, (int) $new_height, self::DEFAULT_QUALITY))->resize($image);
                break;
            case 'image/bmp':
                $image = (new ImageBmp((int) $width, (int) $new_height, self::DEFAULT_QUALITY))->resize($image);
                break;
        }

        return $image;
    }

    /**
     * keepOriginalName
     *
     * @return self
     */
    public function keepOriginalName(): self
    {
        $this->keep_name = true;
        return $this;
    }

    /**
     * Set the image width in px
     *
     * @param   int  $width  The image width in px
     *
     * @return  self
     */
    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    /**
     * Set the image height in px
     *
     * @param   height  $height  The image height in px
     *
     * @return  self
     */
    public function setHeight(int $height)
    {
        $this->height = $height;
    }
}
