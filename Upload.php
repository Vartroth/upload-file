<?php

namespace Molitex\Controller;

/**
 * Realizado por : Adri�n Gonz�lez Ponz
 * Version del Programa : 2.1
 * Fecha de Creaci�n : 15 de Diciembre de 2009
 * Ultima Modificaci�n : 12 de Febrero de 2017
 * 
 * Esta Clase "Upload.class.php" nos permite cargar fichero en nuestra pagina web mediante un formulario "file".
 * La clase detecta el tipo de fichero y almacena los datos de subida del fichero en atributos, tambi�n nos
 * permite almacenar el fichero en una direcci�n definida por el usuario asi como redimensionar en el caso de
 * que el fichero sea una imagen, calculando el el ancho y el alto para mantener la relaci�n de aspeto
 * 
 * Para que el programa admita un mayor numero de ficheros habr�a que a�adir el tipo de fichero en el vector
 * "file_permitido" aunque para las im�genes deberiamos crear los distintas funciones para los distintos tipos que
 * quisieramos a�adir, adem�s de incluirlos en el vector "imag_permitidas"
 * 
 * El C�digo no est� sujeto a ning�n tipo de licencia por lo que todo el contenido de este fichero puede ser
 * utilizado libremente.
 * 
 * Gracias y espero que os sea util
 * 
 * 
 * *********************************
 * * * * * * VERSION 2.1 * * * * * *
 * *********************************
 * 	 En esta versi�n se ha a�adido la posibilidad de cambiar car�cteres que pueden dar problemas a la hora
 * 	 del direcciondo
 * 
 * *********************************
 * * * * * * VERSION 2.0 * * * * * *
 * *********************************
 *   En la versi�n 2.0 del programa, hemos a�adido el poder generar marcas de agua a las im�genes.
 *   Se han correjido una serie de errores en el tratamiento de los fallos de subida de ficheros y se evita la creaci�n
 *   de basura y se reduce la utilizaci�n de memoria   
 */

class Upload {
	
	private $cargado;			//Determina si el Fichero se ha Subido con Exito
	private $procesado;			//Determina si se ha Procesado el Redimensionamiento de la Imagen
	private $redimensionar;		//Booleano que nos Permite Saber si se quiere Redimensionar la Imagen
	private $version_gd;		//Determina la Version de GD de que disponemos en el servidor
    
    private $file_permitidos;	//Vector con los tipos de ficheros permitidos
 	private $max_file_tamano;	//Determina el Tama�o maximo de fichero que se puede subir
	private $error;				//Vector que Determina si se ha Producido un error y el texto del error
	
	private $file_ant_nombre;	//Nombre del Fichero Temporal
	private $file_ant_tipo;		//Tipo de Fichero Temporal
	private $file_ant_ruta;		//Ruta del Fichero Temporal
	private $file_ant_tamano;	//Tama�o del Fichero Temporal
	private $file_ant_ext;		//Extension del Fichero Temporal
	
	private $file_dst_nombre;	//Nombre del Fichero Final
	private $file_dst_tipo;		//Tipo de Fichero Final
	private $file_dst_ruta;		//Ruta del Fichero Final
	private $file_dst_tamano;	//Tama�o del Fichero Final
	
	private $thumb_nombre;		//Nombre del Thumbnail
	private $thumb_tamano;		//Tama�o del Thumbnail
	private $thumb_ruta;		//Ruta del Thumbnail
	
	private $thumb_x;			//Dimension x del Thumbnail
	private $thumb_y;			//Dimension y del Thumbnail
	
	private $file_es_imagen;	//Booleano que nos dice si el Fichero Cargado es una Imagen
	private $imag_permitidas;	//Vector con los tipos de imagenes permitidas
	private $imagen_dst_calidad;//Calidad de la Nueva Imagen
	private $imagen_ant_codigo; //Codigo que define el tipo de Imagen
	
    private $imagen_ant_x;		//Dimension x de la Imagen Original
    private $imagen_ant_y;		//Dimension y de la Imagen Original    
    private $imagen_dst_x;		//Dimension x de la Imagen Nueva
    private $imagen_dst_y;		//Dimension y de la Imagen Nueva

	/**
     * Metodo que permite la eliminaci�n de caracteres especiales del Espa�ol, asi como eliminaci�n
     * de espacios, etc..
     * Caracteres que pueden dar problemas en sistemas basados en UNIX 
     */
    function Revisar_Nombre_Fichero ($nombre){
    	$nuevo_nombre = $nombre;
    	return $nuevo_nombre;
    }
    
    /**
     * Metodo que permite a�adir a la imagen subida una marca de agua que corresponde a una imagen
     *
     * @param String $ruta_marca_agua
     */
	function Introducir_Marca_Agua($ruta_marca_agua,$pos="centro"){
    	if ($this->procesado){
    		if (is_file($ruta_marca_agua)){
		   	 	$im = @imagecreatefrompng($ruta_marca_agua);
		    	switch ($this->file_ant_tipo){
					case "image/jpg":
		        	case "image/jpeg":
		        	case "image/pjpeg":
		        		$im2 = @imagecreatefromjpeg($this->file_dst_ruta.$this->file_dst_nombre);
						break;
				}
				switch ($pos){
					case "centro":
						@imagecopy($im2, $im, ($this->imagen_dst_x-imagesx($im))/2, ($this->imagen_dst_y-imagesy($im))/2, 0, 0, imagesx($im), imagesy($im));
						break;
				}
				@imagejpeg($im2,$this->file_dst_ruta.$this->file_dst_nombre);
				@imagedestroy($im);
				@imagedestroy($im2);
    		}
    	}
    }
    
    /**
     * Permite Asignar la Calidad de la Nueva Imagen que se genera al redimensionar
     *
     * @param int $calidad
     */
    function Asignar_Calidad ($calidad = 100){
    	$this->imagen_dst_calidad = $calidad;
    }
    
    /**
     * Funci�n que nos permite averiguar el Maximo tama�o que permite el servidor que tenga
     * el fichero que intentamos subir.
     * El parametro de entrada indica en que medida debemos mostrar el resultado
     *
     * @param char 
     * @return int
     */
    function Maximo_Tamano_Subida ($unidades="B"){
    	$valor = trim(ini_get('upload_max_filesize'));
        $unidad = strtolower($valor{strlen($valor)-1});
	    switch($unidad) {
            case 'g': $valor *= 1024;
            case 'm': $valor *= 1024;
            case 'k': $valor *= 1024;
        }
        switch(strtoupper($unidades)){
        	case 'GB': $valor /= 1024;
        	case 'MB': $valor /= 1024;
        	case 'KB': $valor /= 1024;
        }
        return $valor;        
    }
    
    /**
     * Funcion que nos permite averiguar la version de GD del servidor en el caso de que tenga.
     * Si no tiene Devolvera un 0
     *
     * @return int
     */
	function gdversion() {
        $gd_version = null;
        if ($gd_version == null) {
            if (function_exists('gd_info')) {
                $gd = gd_info();
                $gd = $gd["GD Version"];
                $regex = "/([\d\.]+)/i";
            } else {
                ob_start();
                phpinfo(8);
                $gd = ob_get_contents();
                ob_end_clean();
                $regex = "/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i";
            }
            
            if (preg_match($regex, $gd, $m)) $gd_version = (float) $m[1];
            else $gd_version = 0;
            
        }
        return $gd_version;
    }
    
    
    /**
     * Permite Inicializar los Atributos del Objeto
     *
     */
	private function Inicializar_Atributos(){
    	
		$this->version_gd = $this->gdversion();
		$this->max_file_tamano = $this->Maximo_Tamano_Subida();
    	$this->cargado = true;
		$this->procesado = true;   
    	$this->redimensionar = false;
		$this->Iniciar_Error();
		
		$this->file_ant_nombre = "";
		$this->file_ant_tipo = "";
		$this->file_ant_ruta = "";
		$this->file_ant_tamano = "";
		$this->file_ant_ext = "";
		
		$this->file_dst_nombre = "";
		$this->file_dst_tipo = "";
		$this->file_dst_ruta = "";
		$this->file_dst_tamano = ""; 
		
		$this->file_es_imagen = false;
		$this->Asignar_Calidad();
		
	    $this->imagen_ant_x = null;
	    $this->imagen_ant_y = null;    
	    $this->imagen_dst_x = 0;
	    $this->imagen_dst_y = 0;
	    
	    $this->thumb_nombre = "";
		$this->thumb_tamano = "";
		$this->thumb_ruta = "";
	
		$this->thumb_x = 100;
		$this->thumb_y = 0;
    }
    
    /**
     * Constructor de la Clase Imagen
     *
     */
    function __construct($extensiones=false){
    	$this->Inicializar_Atributos();
    	if (!$extensiones){$this->file_permitidos = "jpg,jpeg,pdf,ico,png,bmp";}
    	else{$this->file_permitidos = $extensiones;}
    	$this->imag_permitidas = array("image/jpg","image/jpeg","image/pjpeg","image/png","image/gif","image/ico");	
    }
    
    /**
     * Comprueba el Error Producido a partir del codigo de error de $_FILES
     *
     */
    private function Comprobar_Error_Producido($cod_error){
    	$this->cargado = false;
		switch($cod_error){
        	case 0:

        		$this->cargado = true;
                break;
            case 1:
				$this->Asignar_Error("Superado el Tama&ntilde;o M&aacute;ximo de Subida");
                break;
			case 2:
                $this->Asignar_Error("Superado el Tama&ntilde;o M&aacute;ximo de Subida");
                break;
            case 3:
                $this->Asignar_Error("Error al subir el fichero");
                break;
            case 4:

                $this->Asignar_Error("Error al subir el fichero");
                break;
            default:
               $this->Asignar_Error("Desconocido");
		}
    }
    
    /**
     * Genera un nuevo nombre para un fichero
     *
     * @param String $fichero
     * @param Int $contador
     * @return String
     */
    private function Nuevo_Nombre_de_Fichero($fichero,$contador){
    	$nombre = $fichero;
    	$cadena = explode(".",$fichero);
    	switch ($contador){
    		case 2:
    			$nombre = $cadena[0]."(".$contador.")".".".$cadena[1];
    			break;
    		default:
    			$ini = strpos($fichero,"(".($contador-1).")");
    			if ($ini>0){
    				$fin = $ini + strlen("(".($contador-1).")");
    				$nombre = substr_replace($fichero,"(".$contador.")",$ini) . ".".$cadena[sizeof($cadena)-1];
    			}
				
    	}
    	return $nombre;
    	
    }
    
    /**
     * Dado el vector que contiene los datos de un fichero subido por $_FILES permite 
     * subir el fichero al servidor controlando los errores que se pueden producir durante el
     * proceso
     *
     * @param Array $fichero
     * @return Boolean
     */
    function Cargar_Fichero($fichero){
    	$this->Iniciar_Error();

    	$this->Comprobar_Error_Producido($fichero['error']);
		
		if ($this->cargado){
	        $this->file_ant_ruta = $fichero['tmp_name'];
            $this->file_ant_nombre = $this->Revisar_Nombre_Fichero($fichero['name']);
                if ($this->file_ant_nombre == '') {
                    $this->cargado = false;
                    $this->Asignar_Error("Error al subir el fichero el servidor");
                }
		}
		if ($this->cargado){
			
			$this->file_ant_tamano = $fichero['size'];
			$this->file_ant_tipo = $fichero['type'];
			$this->file_ant_ext = substr(strrchr($this->file_ant_nombre,'.'),1);
			if (in_array($this->file_ant_tipo, $this->imag_permitidas)){
                    $this->file_es_imagen = true;
                    $info = @getimagesize($this->file_ant_ruta);
                    $this->imagen_ant_x = $info[0];
                	$this->imagen_ant_y = $info[1];
                	$this->imagen_dst_x = $info[0];
                	$this->imagen_dst_y = $info[1];
                	$this->imagen_ant_codigo = $info[2];
            }
            elseif(!strstr($this->file_permitidos,$this->file_ant_ext)){
				$this->cargado = false;
				//echo($this->file_ant_ext);
                $this->Asignar_Error("Los ficheros del tipo ".$this->file_ant_ext." no est&aacute;n permitidos");
            }
		}
		return $this->cargado;
    }
    
    /**
     * Funcion que Calcula y asigna los datos necesarios para la redimension de una imagen
     *
     * @param Int $ancho
     * @param Int $alto
     */
    function Asignar_Datos_Redimensionar($ancho=null,$alto=null){
    	if ($this->file_es_imagen){
	    	if (!is_null($ancho) && is_numeric($ancho) || !is_null($alto) && is_numeric($alto)){
	    		$this->redimensionar = true;
	    		if (!is_null($alto) && is_null($ancho)){
	    			if ($alto < $this->imagen_ant_y){
	    				$this->imagen_dst_y = $alto;
	    				$ratio = $this->imagen_ant_x/$this->imagen_ant_y;
	    				$this->imagen_dst_x = round(($ratio*$this->imagen_dst_y),0);
	    			}
	    			else{
	    				$this->redimensionar = false;
	    			}
	    		}
	    		elseif (!is_null($ancho) && is_null($alto)){
	    			if ($ancho < $this->imagen_ant_x){
	    				$this->imagen_dst_x = $ancho;
	    				$ratio = $this->imagen_ant_x/$this->imagen_ant_y;
	    				$this->imagen_dst_y = round(($this->imagen_dst_x/$ratio),0);
	    			}
	    			else{
	    				$this->redimensionar = false;
	    			}
	    		}
	    		elseif (!is_null($ancho) && !is_null($alto)){
	    			$this->imagen_dst_x = $ancho;
	    			$this->imagen_dst_y = $alto;
	    		}
	    		
	    	}
    	}
    	else{
    		$this->redimensionar = false;
    	}
    }
    
    /**
     * Funcion que Realiza el Redimensionado de una Imagen en funci�n del tipo de imagen
     * al que corresponda
     *
     * @return Boolean
     */
    private function Procesar_Redimensionado_de_Imagen(){
 		switch ($this->file_ant_tipo){
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/pjpeg':
				$img = @imagecreatefromjpeg($this->file_ant_ruta);
				break;
			case 'image/bmp':
			    $img = @imagecreatefrombmp($this->file_ant_ruta);
			    break;
			case 'image/png':
			    $img = @imagecreatefrompng($this->file_ant_ruta);
			    break;
			default:
				$this->procesado = false;
				$this->Asignar_Error("No ha sido posible subir porque no esta soportado el redimensionado de im&aacute;genes ".$this->file_ant_ext);
				return false;
				break;
		}
		if ($this->procesado && !empty($img)){
			$thumb = imagecreatetruecolor($this->imagen_dst_x,$this->imagen_dst_y);
			switch ($this->file_ant_tipo){
				case 'image/jpg':
				case 'image/jpeg':
				case 'image/pjpeg':
				    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $this->imagen_dst_x, $this->imagen_dst_y, $this->imagen_ant_x, $this->imagen_ant_y);
					imagejpeg($thumb,$this->file_dst_ruta.$this->file_dst_nombre,$this->imagen_dst_calidad);
					break;
				case 'image/bmp':
				    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $this->imagen_dst_x, $this->imagen_dst_y, $this->imagen_ant_x, $this->imagen_ant_y);
				    imagebmp($thumb,$this->file_dst_ruta.$this->file_dst_nombre,$this->imagen_dst_calidad);
				    break;
				case 'image/png':
				    imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
				    imagealphablending($thumb, false);
				    imagesavealpha($thumb, true);
				    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $this->imagen_dst_x, $this->imagen_dst_y, $this->imagen_ant_x, $this->imagen_ant_y);
				    imagepng($thumb,$this->file_dst_ruta.$this->file_dst_nombre,null,$this->imagen_dst_calidad);
			}
			$this->file_dst_tamano = filesize($this->file_dst_ruta.$this->file_dst_nombre);
		}
		return true;
    }
   
    /**
     * Funcion que procesa la parte de redimensionado, copiado y renombrado de los ficheros que
     * son subidos a la base de datos
     *
     * @param String $ruta_destino
     * @return Boolean
     */
    function Procesar ($ruta_destino=null, $nombreFichero=null){

    	$this->procesado = true;
        if (!$this->cargado){
        	$this->procesado = false;
        }
    	
        if ($this->procesado){
        	
        	if (empty($ruta_destino) || is_null($ruta_destino)){
        		$ruta_destino = $this->file_ant_ruta;
        	}
        	else{
        		$this->file_dst_ruta = $ruta_destino;
        	}
        	
        	$this->file_dst_nombre = (is_null($nombreFichero)) ? $this->file_ant_nombre : $nombreFichero ;
        	
        	if (is_null($nombreFichero)){
	        	$this->file_dst_tipo = $this->file_ant_tipo;
				$i=2;
	        	while(file_exists($this->file_dst_ruta.$this->file_dst_nombre)){
	        		$this->file_dst_nombre = $this->Nuevo_Nombre_de_Fichero($this->file_dst_nombre,$i);
	        		$i++;
	        	}
        	}
        	
        	//if (is_file($this->file_dst_ruta.$nombreFichero)){@unlink($this->file_dst_ruta.$nombreFichero);}
        	
        	if($this->file_es_imagen){
				if ($this->redimensionar && $this->version_gd>0){
					if (!$this->Procesar_Redimensionado_de_Imagen()){
						
					}
				}
				else{
					if(!move_uploaded_file($this->file_ant_ruta,$this->file_dst_ruta.$this->file_dst_nombre)){
						$this->procesado = false;
						$this->Asignar_Error("MOVER_FICHERO");
					}
					else{
						$this->file_dst_tamano = filesize($this->file_dst_ruta.$this->file_dst_nombre);
					}
				}
        	}
        	else{
        		if (!move_uploaded_file($this->file_ant_ruta,$this->file_dst_ruta.$this->file_dst_nombre)){
        			$this->procesado = false;
        			$this->Asignar_Error("MOVER_FICHERO");
        		}
        		else{
        			$this->file_dst_tamano = filesize($this->file_dst_ruta.$this->file_dst_nombre);
        		}
        	}
        } 
        return $this->procesado;
    }
    
    
    function setThumbX($ancho=150){
    	$this->thumb_x = $ancho;
    }
    
	function Crear_Thumbnail($ruta){
		$thumb = array();
		if ($this->procesado){
        	
			$this->thumb_ruta = $ruta;
          	$this->thumb_nombre = $this->file_ant_nombre;
          	
			$i=2;
        	while(file_exists($this->thumb_ruta.$this->thumb_nombre)){
        		$this->thumb_nombre = $this->Nuevo_Nombre_de_Fichero($this->thumb_nombre,$i);
        		$i++;
        	}
        	if($this->file_es_imagen){
				if ($this->version_gd>0 && $this->thumb_x < $this->imagen_dst_x){

					if (file_exists($this->file_dst_ruta.$this->file_dst_nombre)){

						$original = imagecreatefromjpeg($this->file_dst_ruta.$this->file_dst_nombre);
						
						$ratio = $this->imagen_dst_x / $this->imagen_dst_y;
						
						$this->thumb_y = round($this->thumb_x / $ratio,0);
						
						$auxthumb = imagecreatetruecolor($this->thumb_x,$this->thumb_y); 
						
						imagecopyresampled($auxthumb,$original,0,0,0,0,$this->thumb_x,$this->thumb_y,$this->imagen_dst_x , $this->imagen_dst_y);
						
						imagejpeg($auxthumb,$this->thumb_ruta.$this->thumb_nombre,$this->imagen_dst_calidad);
						
						if (is_file($this->thumb_ruta.$this->thumb_nombre)){
							$this->thumb_tamano = filesize($this->thumb_ruta.$this->thumb_nombre);
							return true;
						}
						else{
							$this->Asignar_Error("THUMBNAIL");
							$this->procesado = false;
							$nombre_completo = $this->file_dst_ruta.$this->file_dst_nombre; 
							unset($nombre_completo);
						}
					}
				}
				else{
					
					copy($this->file_dst_ruta.$this->file_dst_nombre,$this->thumb_ruta.$this->thumb_nombre);
					$this->thumb_tamano = filesize($this->thumb_ruta.$this->thumb_nombre);
				}
				
        	}
        	else{
        		$this->Asignar_Error("MOVER_FICHERO");
        		$this->procesado = false;
        		$nombre_completo = $this->file_dst_ruta.$this->file_dst_nombre; 
				unset($nombre_completo);
        		}
        		
        	}
        	return false;
	}
    
	/**
     * Funcion que nos permite saber todos los datos acerca del nuevo fichero 
     * que se ha subido el servidor
     *
     * @return Array
     */
    function Devolver_Datos_Fichero(){
    	if ($this->file_es_imagen){
    		$v_datos = array("error"=>$this->error,
    						 "ruta_completa"=>$this->file_dst_ruta.$this->file_dst_nombre,
    						 "nombre"=>$this->file_dst_nombre,
    						 "tipo"=>$this->file_dst_tipo,
    						 "tamano"=>$this->file_dst_tamano,
    						 "anchura"=>$this->imagen_dst_x,
    						 "altura"=>$this->imagen_dst_y,
    						 "dimensiones"=>$this->imagen_dst_x." x ".$this->imagen_dst_y,
    						 "thumb_ruta_completa"=>$this->thumb_ruta.$this->thumb_nombre,
    						 "thumb_nombre"=>$this->thumb_nombre,
    						 "thumb_anchura"=>$this->thumb_x,
    						 "thumb_altura"=>$this->thumb_y,
    						 "thumb_tamano"=>$this->thumb_tamano);
    						
    	}
    	else{
    		$v_datos = array("error"=>$this->error,
    						 "ruta_completa"=>$this->file_dst_ruta.$this->file_dst_nombre,
    						 "nombre"=>$this->file_dst_nombre,
    						 "tipo"=>$this->file_dst_tipo,
    						 "tamano"=>$this->file_dst_tamano." B");
    	}
    	return $v_datos;
    }
    
    
    function Devolver_Nombre_Fichero(){
    	return $this->file_dst_nombre;
    }
    
	/**
     * Funcion que nos permite ver el contenido del atributo error, mas concretamente
     * el texto del error producido
     *
     * @return String
     */
    function Devolver_Error (){
    	return $this->error["texto"];
    }
    
    /**
     * Metodo que inicia el vector error
     *
     */
    function Iniciar_Error(){
    	$this->error["producido"]=false;
    	$this->error["texto"]="";
    }
    
    /**
     * Metodo que Asigna un texto al error producido
     *
     * @param String $error
     */
    function Asignar_Error($error){
    	$this->error["producido"]=true;
    	$this->error["texto"]=$error;
    }
    
    /**
     * Metodo que nos permite averiguar si un fichero es una imagen
     * 
     * @return Boolean
     */

    function Es_Imagen(){
    	return $this->file_es_imagen;
    }
}
?>