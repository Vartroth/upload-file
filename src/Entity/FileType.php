<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

use Vartroth\UploadFile\Language\LangString;

interface FileType
{

    public function __construct(array $FileData, LangString $lang);

    public function validateType(array $typeList): self;

    public function setName(string $name);

    public function getName(): string;

    public function getType(): string;

    public function getSize(): int;

    public function getLang(): LangString;

    public function getKeepName(): bool;

    public function toArray(): array;

    public function toJson(): string;
}
