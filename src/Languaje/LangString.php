<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Language;

abstract class LangString
{
    public const UPLOAD_ERROR = "error";
    public const MIME_TYPE = "type";

    private $traduction = [];

    public function write($key): string
    {
        return $this->traduction[$key];
    }
}
