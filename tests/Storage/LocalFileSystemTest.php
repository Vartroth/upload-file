<?php declare (strict_types = 1);

use PHPUnit\Framework\TestCase;
use Vartroth\UploadFile\Storage\LocalFileSystem;

class LocalFileSystemTest extends TestCase
{
    /**
     * @var string
     */
    protected $assetsDirectory;

    protected function setUp(): void
    {
        $this->assetsDirectory = dirname(__FILE__) . '/assets';
        $_FILES['foo']         = [
            'name'     => 'file name !s invalid.txt',
            'size'     => 1,
            'type'     => 'text/plain',
            'tmp_name' => $this->assetsDirectory . '/file name !s invalid.txt',
            'error'    => 0,
        ];
    }

    public function testConstructionWithInvalidPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        new LocalFileSystem(__DIR__ . "./path");
    }
}
