<?php declare (strict_types = 1);

namespace Vartroth\UploadFile;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vartroth\UploadFile\Entity\Types\Image;
use Vartroth\UploadFile\Exception\UploadFileException;
use Vartroth\UploadFile\Language\LangEs;

class ImageTest extends TestCase
{
    private $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'upload-image-');
        $canvas = imagecreatetruecolor(8, 4);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagesetpixel($canvas, 7, 3, imagecolorallocate($canvas, 255, 0, 0));
        imagepng($canvas, $this->path);
        imagedestroy($canvas);
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    private function image(string $mime = 'image/png', string $name = 'photo.png'): Image
    {
        return new Image([
            'name' => $name,
            'type' => $mime,
            'size' => 123456,
            'tmp_name' => $this->path,
            'error' => 0,
        ], new LangEs());
    }

    public function testUntransformedUploadPreservesBytesAndClientMetadata(): void
    {
        $bytes = file_get_contents($this->path);
        $image = $this->image('image/jpeg', 'original.jpeg')->keepOriginalName();
        $this->assertSame($bytes, file_get_contents($this->path));
        $this->assertSame('image/jpeg', $image->getType());
        $this->assertSame('original.jpeg', $image->getName());
        $this->assertSame(123456, $image->getSize());
        $this->assertSame($this->path, $image->getTmpName());
        $this->assertTrue($image->getKeepName());
    }

    #[DataProvider('targets')]
    public function testConversionWithoutResizeUpdatesBytesAndMetadata(string $mime, string $encoder, string $decoder, string $extension): void
    {
        if (! function_exists($encoder) || ! function_exists($decoder)) {
            $this->markTestSkipped('GD codec unavailable: ' . $mime);
        }
        $image = $this->image('image/jpeg', 'photo.original.jpeg')->keepOriginalName();
        $this->assertSame($image, $image->convertTo($mime));
        $bytes = file_get_contents($this->path);
        $info = getimagesizefromstring($bytes);
        $this->assertSame($mime, $info['mime']);
        $this->assertSame($mime, $image->getType());
        $this->assertSame('photo.original.' . $extension, $image->getName());
        $this->assertSame(strlen($bytes), $image->getSize());
        $this->assertSame($image->getSize(), $image->toArray()['size']);
        $this->assertSame(8, $image->getWidth());
        $this->assertSame(4, $image->getHeight());
        $this->assertSame($this->path, $image->getTmpName());
        $this->assertTrue($image->getKeepName());
        $decoded = $decoder($this->path);
        $color = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
        if ($mime === 'image/jpeg') {
            $this->assertGreaterThan(240, $color['red']);
            $this->assertGreaterThan(240, $color['green']);
            $this->assertGreaterThan(240, $color['blue']);
        } else {
            $this->assertSame(127, $color['alpha']);
        }
        imagedestroy($decoded);
        $image->convertTo('image/png');
        $this->assertSame('image/png', getimagesize($this->path)['mime']);
        $this->assertSame('photo.original.png', $image->getName());
    }

    public static function targets(): array
    {
        return [
            'PNG' => ['image/png', 'imagepng', 'imagecreatefrompng', 'png'],
            'JPEG' => ['image/jpeg', 'imagejpeg', 'imagecreatefromjpeg', 'jpg'],
            'WebP' => ['image/webp', 'imagewebp', 'imagecreatefromwebp', 'webp'],
        ];
    }

    public function testResizeDetectsActualMimeAndPreservesAlpha(): void
    {
        $image = $this->image('image/webp');
        $this->assertSame($image, $image->resizeImage(4));
        $this->assertSame('image/png', $image->getType());
        $this->assertSame(4, $image->getWidth());
        $this->assertSame(2, $image->getHeight());
        $this->assertSame(filesize($this->path), $image->getSize());
        $decoded = imagecreatefrompng($this->path);
        $this->assertSame(127, imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha']);
        imagedestroy($decoded);
    }

    public function testResizeNeverUpscalesOrReencodesWhenWidthDoesNotDecrease(): void
    {
        $image = $this->image();
        $before = file_get_contents($this->path);
        $metadata = $image->toArray();
        $image->resizeImage(8)->resizeImage(16);
        $this->assertSame($before, file_get_contents($this->path));
        $this->assertSame($metadata, $image->toArray());
    }

    public function testConversionCoexistsWithResizeInEitherOrder(): void
    {
        $image = $this->image()->convertTo('image/png', 0)->resizeImage(4, 3)->convertTo('image/png', 100);
        $this->assertSame(4, $image->getWidth());
        $this->assertSame(3, $image->getHeight());
        $this->assertSame('image/png', getimagesize($this->path)['mime']);
        $this->assertSame(strlen(file_get_contents($this->path)), $image->getSize());
    }

    public function testWebpResizeKeepsWebpSignature(): void
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('GD WebP codec unavailable');
        }
        $image = $this->image()->convertTo('image/webp')->resizeImage(4);
        $bytes = file_get_contents($this->path);
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WEBP', substr($bytes, 8, 4));
        $this->assertSame('image/webp', getimagesize($this->path)['mime']);
        $this->assertSame('image/webp', $image->getType());
        $this->assertSame('photo.webp', $image->getName());
        $this->assertSame(4, $image->getWidth());
        $this->assertSame(2, $image->getHeight());
        $this->assertSame(strlen($bytes), $image->getSize());
    }

    #[DataProvider('invalidConversions')]
    public function testInvalidConversionDoesNotMutateUpload(string $target, int $quality): void
    {
        $image = $this->image();
        $before = file_get_contents($this->path);
        $metadata = $image->toArray();
        try {
            $image->convertTo($target, $quality);
            $this->fail('Invalid conversion must throw');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame($before, file_get_contents($this->path));
            $this->assertSame($metadata, $image->toArray());
        }
    }

    public static function invalidConversions(): array
    {
        return [['webp', 90], ['image/jpg', 90], ['image/gif', 90], ['image/png', -1], ['image/png', 101]];
    }

    #[DataProvider('invalidDimensions')]
    public function testInvalidResizeDimensionsThrow(int $width, int $height): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->image()->resizeImage($width, $height);
    }

    public static function invalidDimensions(): array
    {
        return [[0, 0], [-1, 0], [4, -1]];
    }

    public function testMissingOutputCodecFailsWithoutMutation(): void
    {
        $image = $this->image();
        $before = file_get_contents($this->path);
        $metadata = $image->toArray();
        $checked = 0;
        foreach (self::targets() as list($mime, $encoder)) {
            if (function_exists($encoder)) {
                continue;
            }
            try {
                $image->convertTo($mime);
                $this->fail('Missing output codec must throw');
            } catch (UploadFileException $exception) {
                $this->assertStringContainsString('codec unavailable', $exception->getMessage());
                $this->assertSame($before, file_get_contents($this->path));
                $this->assertSame($metadata, $image->toArray());
                $checked++;
            }
        }
        if ($checked === 0) {
            $this->markTestSkipped('All target codecs available');
        }
    }

    public function testCorruptImageIsRejectedAtConstruction(): void
    {
        file_put_contents($this->path, 'not an image');
        $this->expectException(UploadFileException::class);
        $this->image();
    }

    public function testUndecodableImageFailsWithoutMutation(): void
    {
        $bytes = substr(file_get_contents($this->path), 0, 33);
        file_put_contents($this->path, $bytes);
        $image = $this->image();
        $metadata = $image->toArray();
        try {
            $image->convertTo('image/png');
            $this->fail('Undecodable image must throw');
        } catch (UploadFileException $exception) {
            $this->assertStringContainsString('decode', $exception->getMessage());
            $this->assertSame($bytes, file_get_contents($this->path));
            $this->assertSame($metadata, $image->toArray());
        }
    }

    public function testAnimatedPngIsRejectedWithoutMutation(): void
    {
        $bytes = file_get_contents($this->path);
        $chunk = 'acTL' . pack('NN', 2, 0);
        $bytes = substr($bytes, 0, 33) . pack('N', 8) . $chunk . pack('N', crc32($chunk)) . substr($bytes, 33);
        file_put_contents($this->path, $bytes);
        $image = $this->image();
        try {
            $image->convertTo('image/png');
            $this->fail('Animation must not be flattened');
        } catch (UploadFileException $exception) {
            $this->assertStringContainsString('Animated', $exception->getMessage());
            $this->assertSame($bytes, file_get_contents($this->path));
        }
    }

    public function testGifCanBeUploadedButTransformationIsRejected(): void
    {
        file_put_contents($this->path, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $image = $this->image('image/gif', 'photo.gif');
        $this->assertSame('photo.gif', $image->getName());
        $this->expectException(UploadFileException::class);
        $this->expectExceptionMessage('Unsupported raster transformation');
        $image->convertTo('image/png');
    }

    public function testAnimatedWebpIsRejectedBeforeCodecLookup(): void
    {
        $chunks = 'VP8X' . pack('V', 10) . "\x02\x00\x00\x00\x07\x00\x00\x03\x00\x00"
            . 'ANIM' . pack('V', 6) . str_repeat("\x00", 6);
        $bytes = 'RIFF' . pack('V', strlen($chunks) + 4) . 'WEBP' . $chunks;
        file_put_contents($this->path, $bytes);
        $image = $this->image('image/webp', 'animated.webp');
        try {
            $image->resizeImage(4);
            $this->fail('Animation must not be flattened');
        } catch (UploadFileException $exception) {
            $this->assertStringContainsString('Animated', $exception->getMessage());
            $this->assertSame($bytes, file_get_contents($this->path));
        }
    }

    public function testPngConversionPreservesPartialAlpha(): void
    {
        $canvas = imagecreatefrompng($this->path);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagesetpixel($canvas, 0, 0, imagecolorallocatealpha($canvas, 20, 40, 60, 63));
        imagepng($canvas, $this->path);
        imagedestroy($canvas);
        $this->image()->convertTo('image/png');
        $canvas = imagecreatefrompng($this->path);
        $color = imagecolorsforindex($canvas, imagecolorat($canvas, 0, 0));
        $this->assertSame(['red' => 20, 'green' => 40, 'blue' => 60, 'alpha' => 63], $color);
        imagedestroy($canvas);
    }

    public function testBmpConversionChangesActualFormatWithoutResizing(): void
    {
        if (! function_exists('imagebmp') || ! function_exists('imagecreatefrombmp')) {
            $this->markTestSkipped('GD BMP codec unavailable');
        }
        $canvas = imagecreatetruecolor(8, 4);
        imagebmp($canvas, $this->path);
        imagedestroy($canvas);
        $this->assertSame('image/bmp', getimagesize($this->path)['mime']);
        $image = $this->image('image/png', 'photo.bmp')->convertTo('image/png');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr(file_get_contents($this->path), 0, 8));
        $this->assertSame('image/png', $image->getType());
        $this->assertSame('photo.png', $image->getName());
        $this->assertFalse($image->getKeepName());
        $this->assertSame(8, $image->getWidth());
        $this->assertSame(4, $image->getHeight());
    }

    public function testWriteFailureThrowsAndPreservesMetadata(): void
    {
        $image = $this->image();
        $bytes = file_get_contents($this->path);
        $metadata = $image->toArray();
        chmod($this->path, 0400);
        clearstatcache(true, $this->path);
        try {
            if (is_writable($this->path)) {
                $this->markTestSkipped('Runtime bypasses file permissions');
            }
            try {
                $image->convertTo('image/png');
                $this->fail('Write failure must throw');
            } catch (UploadFileException $exception) {
                $this->assertStringContainsString('write', $exception->getMessage());
                $this->assertSame($bytes, file_get_contents($this->path));
                $this->assertSame($metadata, $image->toArray());
            }
        } finally {
            chmod($this->path, 0600);
        }
    }
}
