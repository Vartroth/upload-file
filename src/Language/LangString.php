<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Language;

abstract class LangString
{
    public const UPLOAD_ERROR = "error";
    public const MIME_TYPE = "type";
    public const PATH_NOT_EXIST = "path";

    protected $traduction = "";

    abstract public function __construct();

    /**
     * write
     *
     * @param mixed $key
     *
     * @return string
     */
    public function write($key): string
    {
        if (!isset($this->traduction[$key])) {
            throw new \InvalidArgumentException("Traduction string not found");
        }
        return $this->traduction[$key];
    }
}
