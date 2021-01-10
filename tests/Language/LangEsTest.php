<?php declare (strict_types = 1);

use PHPUnit\Framework\TestCase;
use Vartroth\UploadFile\Language\LangEs;

class LangEsTest extends TestCase
{

    public function testInvalidKeyForTranslation()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new LangEs())->write('invalid_key_for_translation');
    }
}
