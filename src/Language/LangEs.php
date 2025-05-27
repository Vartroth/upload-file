<?php

declare (strict_types = 1);

namespace Vartroth\UploadFile\Language;

class LangEs extends LangString
{
    public function __construct()
    {
        $this->traduction = [
            self::UPLOAD_ERROR   => "Error al subir el fichero",
            self::MIME_TYPE      => "Tipo de fichero no válido",
            self::PATH_NOT_EXIST => "La ruta no existe",
        ];
    }

}
