<?php declare (strict_types = 1);

namespace Vartroth\UploadFile\Entity;

class GdVersion
{

    /**
     * Get true if gdversion exists
     *
     * @return boolean
     */
    public function __invoke()
    {
        $gd_version = null;
        if ($gd_version == null) {
            if (function_exists('gd_info')) {
                $gd    = gd_info();
                $gd    = $gd["GD Version"];
                $regex = "/([\d\.]+)/i";
            } else {
                ob_start();
                phpinfo(8);
                $gd = ob_get_contents();
                ob_end_clean();
                $regex = "/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i";
            }

            if (preg_match($regex, $gd, $m)) {
                $gd_version = (float) $m[1];
            } else {
                $gd_version = 0;
            }

        }
        return $gd_version;
    }

}
