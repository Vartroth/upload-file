<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

use Vartroth\UploadFile\Language\LangString;

interface FileType
{

    public function __construct(array $FileData, LangString $lang);

    public function toArray(): array;

    public function toJson(): string;

    public function getLang(): LangString;

}
