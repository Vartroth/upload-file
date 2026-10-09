# upload-file

## Development

Install dependencies and run tests, syntax checks, and debug-dump checks:

```sh
composer install
composer test
```

Debug-call checks use PHP_CodeSniffer with only `Generic.PHP.ForbiddenFunctions`
enabled in `phpcs.xml.dist`. Calls to `var_dump`, `print_r`, and `var_export` are
forbidden, including calls that return a string via a second argument of `true`.
Comments and strings are not flagged; `vendor` and `index.php` are excluded.
This replaces `php-parallel-lint/php-var-dump-check`, whose implicit nullable
parameters emit deprecations on PHP 8.4.

The library requires PHP >=8.4; the development test runner requires PHP >=8.4.1.
`composer test` does not collect coverage. To generate an HTML coverage report
in `.phpunit.cache/coverage` with Xdebug installed:

```sh
XDEBUG_MODE=coverage composer test:coverage
```

Image tests skip JPEG/WebP cases when the corresponding GD codecs are unavailable.

## Image conversion

Conversion is opt-in and runs immediately, even without resizing:

```php
use Vartroth\UploadFile\Entity\Types\Image;
use Vartroth\UploadFile\Language\LangEs;

$image = (new Image($_FILES['photo'], new LangEs()))
    ->keepOriginalName()
    ->convertTo('image/webp', 90);
```

Pass exactly `image/webp`, `image/jpeg`, or `image/png`. Quality is an integer
from 0 to 100 (default 90); PNG maps it to compression, not visual quality.
Conversion and resizing can be called in either order. Resizing only reduces
width; omitted height preserves the aspect ratio. Resizing uses quality 90;
call `convertTo()` after resizing to control the final encoding quality.

Successful conversion updates MIME, extension (`.webp`, `.jpg`, `.png`), size,
and dimensions. `keepOriginalName()` keeps the filename stem, with the new
extension. The upload temporary path stays unchanged for the existing save flow.
Without transformation, upload bytes and supplied metadata remain unchanged.

Transformations detect the actual input format and require its GD decoder and
the output encoder. Static JPEG, PNG, WebP, and BMP inputs are supported. Animated
PNG/WebP and all GIF/AVIF transformations are rejected to avoid silently losing
frames; these formats can still be uploaded unchanged. PNG/WebP retain alpha;
JPEG/BMP flatten transparency onto white. GD re-encoding does not preserve EXIF
or other ancillary metadata. Invalid arguments raise `InvalidArgumentException`;
invalid images, unsupported transformations, unavailable codecs, and encoding or
write failures raise `UploadFileException`.
