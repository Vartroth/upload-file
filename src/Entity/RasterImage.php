<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

use Vartroth\UploadFile\Exception\UploadFileException;

class RasterImage
{
    private const CODECS = [
        'image/jpeg' => ['imagecreatefromjpeg', 'imagejpeg'],
        'image/png' => ['imagecreatefrompng', 'imagepng'],
        'image/webp' => ['imagecreatefromwebp', 'imagewebp'],
        'image/bmp' => ['imagecreatefrombmp', 'imagebmp'],
    ];

    public static function encode(string $path, string $mime, int $width, int $height, int $quality): string
    {
        $info = @getimagesize($path);
        $bytes = @file_get_contents($path);
        if ($info === false || $bytes === false) {
            throw new UploadFileException('Invalid raster image');
        }
        $sourceMime = $info['mime'];
        if (! isset(self::CODECS[$sourceMime], self::CODECS[$mime])) {
            throw new UploadFileException('Unsupported raster transformation: ' . $sourceMime);
        }
        self::rejectAnimation($bytes, $sourceMime);
        list($decoder) = self::CODECS[$sourceMime];
        list(, $encoder) = self::CODECS[$mime];
        if (! function_exists($decoder) || ! function_exists($encoder)) {
            throw new UploadFileException('Required GD codec unavailable: ' . $sourceMime . ' -> ' . $mime);
        }

        $origin = @$decoder($path);
        if ($origin === false) {
            throw new UploadFileException('Unable to decode raster image');
        }
        $canvas = null;
        try {
            $canvas = imagecreatetruecolor($width, $height);
            if ($canvas === false) {
                throw new UploadFileException('Unable to allocate image');
            }
            if ($mime === 'image/jpeg' || $mime === 'image/bmp') {
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            } else {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            }
            if (! imagecopyresampled($canvas, $origin, 0, 0, 0, 0, $width, $height, imagesx($origin), imagesy($origin))) {
                throw new UploadFileException('Unable to resize raster image');
            }

            ob_start();
            try {
                if ($mime === 'image/png') {
                    $success = @$encoder($canvas, null, (int) round((100 - $quality) * 9 / 100));
                } elseif ($mime === 'image/bmp') {
                    $success = @$encoder($canvas);
                } else {
                    $success = @$encoder($canvas, null, $quality);
                }
                $output = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            $result = @getimagesizefromstring($output);
            if (! $success || $result === false || $result['mime'] !== $mime || $result[0] !== $width || $result[1] !== $height) {
                throw new UploadFileException('Unable to encode raster image');
            }
            return $output;
        } finally {
            imagedestroy($origin);
            if ($canvas !== null && $canvas !== false) {
                imagedestroy($canvas);
            }
        }
    }

    private static function rejectAnimation(string $bytes, string $mime): void
    {
        if ($mime !== 'image/png' && $mime !== 'image/webp') {
            return;
        }
        $offset = $mime === 'image/png' ? 8 : 12;
        $size = strlen($bytes);
        while ($offset + 8 <= $size) {
            if ($mime === 'image/png') {
                $length = unpack('Nlength', substr($bytes, $offset, 4))['length'];
                $chunk = substr($bytes, $offset + 4, 4);
                $step = $length + 12;
            } else {
                $chunk = substr($bytes, $offset, 4);
                $length = unpack('Vlength', substr($bytes, $offset + 4, 4))['length'];
                $step = 8 + $length + ($length % 2);
            }
            if ($step > $size - $offset) {
                throw new UploadFileException('Invalid raster chunks');
            }
            if ($chunk === 'acTL' || $chunk === 'ANIM' || $chunk === 'ANMF'
                || ($chunk === 'VP8X' && $length > 0 && (ord($bytes[$offset + 8]) & 2))) {
                throw new UploadFileException('Animated images cannot be transformed');
            }
            $offset += $step;
        }
    }
}
