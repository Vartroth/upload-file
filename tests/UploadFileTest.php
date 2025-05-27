<?php declare (strict_types = 1);

namespace Vartroth\UploadFile;

use PHPUnit\Framework\TestCase;
use Vartroth\UploadFile\Language\LangEs;
use Vartroth\UploadFile\Entity\Types\File;

class UploadFileTest extends TestCase
{

    /**
     * @var string
     */
    protected $assetsDirectory;

    protected function setUp(): void
    {
        $this->assetsDirectory = dirname(__FILE__) . '/assets';
        $_FILES['foo']         = [
            'name'     => 'foo.txt',
            'size'     => 222222,
            'type'     => 'text/plain',
            'tmp_name' => $this->assetsDirectory . '/foo.txt',
            'error'    => 0,
        ];
    }

    public function testConstructionWithInvalidArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new File([], new LangEs());

    }

    public function testConstructionWithUploadError()
    {
        $this->expectException(\Vartroth\UploadFile\Exception\UploadFileException::class);
        new File(["error" => 1], new LangEs());
    }

    public function testValidateInvalidTypeGenerateNewException()
    {
        $this->expectException(\Vartroth\UploadFile\Exception\UploadFileException::class);
        $file = new File($_FILES['foo'], new LangEs());
        $file->validateType(["aplication/pdf"]);
    }

    public function testValidateTypeAndResultIsSameObject()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertEquals($file, $file->validateType(['text/plain']));
    }

    public function testGetName()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $this->assertSame("foo.txt", $file->getName());
    }

    public function testSetName()
    {
        $file = new File($_FILES['foo'], new LangEs());
        $file->setName("testname.txt");
        $this->assertSame("testname.txt", $file->getName());
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

    public function testGetLang()
    {
        $lang = new LangEs();
        $file = new File($_FILES['foo'], $lang);
        $this->assertSame($lang, $file->getLang());
    }

    public function testGetKeepNameAndUniqueName()
    {
        $lang  = new LangEs();
        $file  = new File($_FILES['foo'], $lang);
        $file2 = new File($_FILES['foo'], $lang);
        $file2->keepOriginalName();
        $this->assertSame(false, $file->getKeepName());
        $this->assertSame(true, $file2->getKeepName());
    }

    public function testToArray()
    {
        $expected = [
            'name'     => 'foo.txt',
            'type'     => 'text/plain',
            'size'     => 222222,
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
