<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity\Types;

use InvalidArgumentException;
use Vartroth\UploadFile\Entity\FileType;
use Vartroth\UploadFile\Entity\RasterImage;
use Vartroth\UploadFile\Exception\UploadFileException;
use Vartroth\UploadFile\Language\LangString;

class Image implements FileType
{

    const IMAGE_SIZE_WIDTH  = 0;
    const IMAGE_SIZE_HEIGHT = 1;
    const DEFAULT_QUALITY   = 90;
    const VALID_MIME_TYPES  = [
        'image/jpg',
        'image/jpeg',
        'image/pjpeg',
        'image/bmp',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
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
     * @var string
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

        if (! isset($FileData) || ! isset($FileData['error'])) {
            throw new InvalidArgumentException($this->lang->write($this->lang::UPLOAD_ERROR));
        }

        if ($FileData['error'] || ! is_file($FileData['tmp_name'])) {
            throw new UploadFileException($this->lang->write($this->lang::UPLOAD_ERROR));
        }

        $info = @\getimagesize($FileData['tmp_name']);
        if ($info === false) {
            throw new UploadFileException('Invalid raster image');
        }

        $this->name      = $FileData['name'];
        $this->size      = $FileData['size'];
        $this->type      = $FileData['type'];
        $this->tmp_name  = $FileData['tmp_name'];
        $this->keep_name = false;
        $this->width     = (int) $info[self::IMAGE_SIZE_WIDTH];
        $this->height    = (int) $info[self::IMAGE_SIZE_HEIGHT];

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
     * @return  int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Get the temporary file name
     *
     * @return string
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
            'name'     => $this->name,
            'type'     => $this->type,
            'size'     => $this->size,
            'tmp_name' => $this->tmp_name,
            'width'    => $this->width,
            'height'   => $this->height,
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

        if (! $valid) {
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
        if ($width <= 0 || $height < 0) {
            throw new InvalidArgumentException('Width must be positive and height must be non-negative');
        }

        $info = @getimagesize($this->tmp_name);
        if ($info === false) {
            throw new UploadFileException('Invalid raster image');
        }
        if ($width >= $info[0]) {
            return $this;
        }

        $height = $height ?: max(1, (int) ($info[1] * $width / $info[0]));
        return $this->transform($info['mime'], $width, $height, self::DEFAULT_QUALITY, false);
    }

    public function convertTo(string $targetMime, int $quality = self::DEFAULT_QUALITY): self
    {
        if (! in_array($targetMime, ['image/webp', 'image/jpeg', 'image/png'], true)) {
            throw new InvalidArgumentException('Target MIME must be image/webp, image/jpeg or image/png');
        }
        if ($quality < 0 || $quality > 100) {
            throw new InvalidArgumentException('Quality must be between 0 and 100');
        }

        $info = @getimagesize($this->tmp_name);
        if ($info === false) {
            throw new UploadFileException('Invalid raster image');
        }
        return $this->transform($targetMime, $info[0], $info[1], $quality, true);
    }

    private function transform(string $mime, int $width, int $height, int $quality, bool $rename): self
    {
        $output = RasterImage::encode($this->tmp_name, $mime, $width, $height, $quality);
        $original = @file_get_contents($this->tmp_name);
        if ($original === false) {
            throw new UploadFileException('Unable to read image');
        }

        // Keep the HTTP upload path registered for move_uploaded_file().
        if (@file_put_contents($this->tmp_name, $output) !== strlen($output)) {
            @file_put_contents($this->tmp_name, $original);
            throw new UploadFileException('Unable to write transformed image');
        }

        $this->type = $mime;
        $this->size = strlen($output);
        $this->width = $width;
        $this->height = $height;
        if ($rename) {
            $extensions = ['image/webp' => 'webp', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
            $extension = pathinfo($this->name, PATHINFO_EXTENSION);
            $stem = $extension === '' ? $this->name : substr($this->name, 0, -strlen($extension) - 1);
            $this->name = $stem . '.' . $extensions[$mime];
        }
        return $this;
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
