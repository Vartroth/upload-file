<?php declare (strict_types = 1);

namespace Vartroth\UploadFile;

use PHPUnit\Framework\TestCase;
use Vartroth\UploadFile\Entity\Types\File;
use Vartroth\UploadFile\Language\LangEs;

class UploadFileTest extends TestCase
{

    protected function setUp(): void
    {
        $this->assetsDirectory = dirname(__FILE__) . '/assets';
        $_FILES['foo'] = [
            'name' => 'foo.txt',
            'size' => 222222,
            'type' => 'text/plain',
            'tmp_name' => $this->assetsDirectory . '/foo.txt',
            'error' => 0,
        ];
    }

    public function testConstructionWithInvalidArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new File(array(), new LangEs());

    }

    public function testConstructionWithUploadError()
    {
        $this->expectException(\Vartroth\UploadFile\Exception\UploadFileException::class);
        new File(array("error" => 1), new LangEs());
    }

    public function testValidateInvalidType()
    {
        $this->expectException(\Vartroth\UploadFile\Exception\UploadFileException::class);
        (new File($_FILES['foo'], new LangEs()))->validateType();
    }

    public function testGetName()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame("foo.txt", $file->getName());
    }

    public function testGetSize()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame(222222, $file->getSize());
    }

    public function testGetType()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame("text/plain", $file->getType());
    }

    public function testToArray()
    {
        $expected = [
            'name' => 'foo.txt',
            'type' => 'text/plain',
            'size' => 222222,
            'tmp_name' => $this->assetsDirectory . '/foo.txt',
        ];
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame($expected, $file->toArray());
        return $expected;
    }

    /**
     * @depends testToArray
     */
    public function testToJson(array $expected)
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame(json_encode($expected), $file->toJson());
    }

}
