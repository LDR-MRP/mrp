<?php 
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'Libraries/phpmailer/Exception.php';
    require 'Libraries/phpmailer/PHPMailer.php';
    require 'Libraries/phpmailer/SMTP.php';

	//Retorla la url del proyecto
	function base_url()
	{
		return BASE_URL;
	}
    //Retorla la url de Assets
    function media()
    {
        return BASE_URL."/Assets";
    }
    function headerAdmin($data="")
    {
        $view_header = "Views/Template/header_admin.php";
        require_once ($view_header);
    }
    function footerAdmin($data="")
    {
        $view_footer = "Views/Template/footer_admin.php";
        require_once ($view_footer);        
    }

	//Muestra información formateada
	function dep($data)
    {
        $format  = print_r('<pre>');
        $format .= print_r($data);
        $format .= print_r('</pre>');
        return $format;
    }
    function getModal(string $nameModal, $data)
    {
        $view_modal = "Views/Template/Modals/{$nameModal}.php";
        require_once $view_modal;        
    }
    function getFile(string $url, $data)
    {
        ob_start();
        require_once("Views/{$url}.php");
        $file = ob_get_clean();
        return $file;        
    }
    //Envio de correos

  function sendMailLocal($data, $template, $correos_copia){
    $mail = new PHPMailer(true);
    ob_start();
    require_once("Views/Template/Email/".$template.".php");
    $mensaje = ob_get_clean();

    $cc = explode(",", $correos_copia);

    try {
        // Configuración del servidor SMTP
        $mail->SMTPDebug = 0;                      // Cambiar a 0 en producción
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'notificacion@ldrsolutions.com.mx';
        $mail->Password   = 'ppiz zylc bpod tczi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Remitente y destinatarios
        $mail->setFrom('notificacion@ldrsolutions.com.mx', 'Notificaciones LDR');
        $mail->addAddress($data['email']);
        
        foreach ($cc as $correo_copia) {
            $mail->addCC(trim($correo_copia)); // Limpia espacios por si acaso
        }

        // Contenido del correo LDR
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';       //  Para tildes, eñes, etc.
        $mail->Encoding = 'base64';     //  Recomendado con HTML + UTF-8
        $mail->Subject = $data['asunto'];
        $mail->Body    = $mensaje;
        $mail->addCustomHeader('X-Mailer', 'PHPMailer');

        $mail->send();
         return true;
    } catch (Exception $e) {
        echo "Error en el envío del mensaje: {$mail->ErrorInfo}";
         return false;
    }
}



function formatFechaLargaEs(string $value, bool $incluirHora = false): string
{
    $value = trim($value);
    if ($value === '') return '';


    $formatos = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
        'Y-m-d',
    ];

    $dt = null;
    foreach ($formatos as $fmt) {
        $dtTry = DateTime::createFromFormat($fmt, $value);
        if ($dtTry instanceof DateTime) {
      
            $errors = DateTime::getLastErrors();
            if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                $dt = $dtTry;
                break;
            }
        }
    }

   
    if (!$dt) {
        try {
            $dt = new DateTime($value);
        } catch (Exception $e) {
            return $value; 
        }
    }

    $meses = [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
        7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
    ];

    $dia  = $dt->format('d');
    $mes  = $meses[(int)$dt->format('n')] ?? '';
    $anio = $dt->format('Y');

    if (!$incluirHora) {
        return "{$dia} de {$mes} de {$anio}";
    }

    $hora = $dt->format('H:i');
    return "{$dia} de {$mes} de {$anio} {$hora}";
}




function sendMailLocalCron($data, $template, $correos_copia = ''){
    $mail = new PHPMailer(true);
    ob_start();
    require("Views/Template/Email/".$template.".php"); 
    $mensaje = ob_get_clean();

    $cc = explode(",", $correos_copia);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'notificacion@ldrsolutions.com.mx';
        $mail->Password   = 'ppiz zylc bpod tczi';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('notificacion@ldrsolutions.com.mx', 'Notificaciones LDR');
          $mail->addAddress($data['email']);
        
  $cc = is_array($correos_copia) ? $correos_copia : explode(",", (string)$correos_copia);
  foreach ($cc as $correo) {
    $correo = trim($correo);
    if ($correo !== '') $mail->addBCC($correo);
  }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = $data['asunto'] . ' | ' . $data['num_orden'] . '';
        $mail->Body    = $mensaje;

        $mail->addCustomHeader('X-Mailer', 'PHPMailer');

        $mail->send();

        return true;
    } catch (Exception $e) {
        error_log("Error en el envío del mensaje a {$data['email']}: {$mail->ErrorInfo}");
        return false;
    }
}




    function getPermisos(int $idmodulo){
        require_once ("Models/PermisosModel.php");
        $objPermisos = new PermisosModel();
        if(!empty($_SESSION['userData'])){
            $idrol = $_SESSION['userData']['idrol'];
            $arrPermisos = $objPermisos->permisosModulo($idrol);
            $permisos = '';
            $permisosMod = '';
            if(count($arrPermisos) > 0 ){
                $permisos = $arrPermisos;
                $permisosMod = isset($arrPermisos[$idmodulo]) ? $arrPermisos[$idmodulo] : "";
            }
            $_SESSION['permisos'] = $permisos;
            $_SESSION['permisosMod'] = $permisosMod;
        }
    }

    function sessionUser(int $idpersona){
        require_once ("Models/LoginModel.php");
        $objLogin = new LoginModel();
        $request = $objLogin->sessionLogin($idpersona);
        return $request;
    }

    function uploadImage(array $data, string $name){
        $url_temp = $data['tmp_name'];
        $destino    = 'Assets/images/uploads/'.$name;        
        $move = move_uploaded_file($url_temp, $destino);
        return $move;
    }

    function deleteFile(string $name){
        unlink('Assets/images/uploads/'.$name);
    }

    //Elimina exceso de espacios entre palabras
    function strClean($strCadena){
        $string = preg_replace(['/\s+/','/^\s|\s$/'],[' ',''], $strCadena);
        $string = trim($string); //Elimina espacios en blanco al inicio y al final
        $string = stripslashes($string); // Elimina las \ invertidas
        $string = str_ireplace("<script>","",$string);
        $string = str_ireplace("</script>","",$string);
        $string = str_ireplace("<script src>","",$string);
        $string = str_ireplace("<script type=>","",$string);
        $string = str_ireplace("SELECT * FROM","",$string);
        $string = str_ireplace("DELETE FROM","",$string);
        $string = str_ireplace("INSERT INTO","",$string);
        $string = str_ireplace("SELECT COUNT(*) FROM","",$string);
        $string = str_ireplace("DROP TABLE","",$string);
        $string = str_ireplace("OR '1'='1","",$string);
        $string = str_ireplace('OR "1"="1"',"",$string);
        $string = str_ireplace('OR ´1´=´1´',"",$string);
        $string = str_ireplace("is NULL; --","",$string);
        $string = str_ireplace("is NULL; --","",$string);
        $string = str_ireplace("LIKE '","",$string);
        $string = str_ireplace('LIKE "',"",$string);
        $string = str_ireplace("LIKE ´","",$string);
        $string = str_ireplace("OR 'a'='a","",$string);
        $string = str_ireplace('OR "a"="a',"",$string);
        $string = str_ireplace("OR ´a´=´a","",$string);
        $string = str_ireplace("OR ´a´=´a","",$string);
        $string = str_ireplace("--","",$string);
        $string = str_ireplace("^","",$string);
        $string = str_ireplace("[","",$string);
        $string = str_ireplace("]","",$string);
        $string = str_ireplace("==","",$string);
        return $string;
    }

    function clear_cadena(string $cadena){
        //Reemplazamos la A y a
        $cadena = str_replace(
        array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
        array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
        $cadena
        );
 
        //Reemplazamos la E y e
        $cadena = str_replace(
        array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
        array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
        $cadena );
 
        //Reemplazamos la I y i
        $cadena = str_replace(
        array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
        array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
        $cadena );
 
        //Reemplazamos la O y o
        $cadena = str_replace(
        array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
        array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
        $cadena );
 
        //Reemplazamos la U y u
        $cadena = str_replace(
        array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
        array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
        $cadena );
 
        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
        array('Ñ', 'ñ', 'Ç', 'ç',',','.',';',':'),
        array('N', 'n', 'C', 'c','','','',''),
        $cadena
        );
        return $cadena;
    }
    //Genera una contraseña de 10 caracteres
	function passGenerator($length = 10)
    {
        $pass = "";
        $longitudPass=$length;
        $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $longitudCadena=strlen($cadena);

        for($i=1; $i<=$longitudPass; $i++)
        {
            $pos = rand(0,$longitudCadena-1);
            $pass .= substr($cadena,$pos,1);
        }
        return $pass;
    }
    //Genera un token
    function token()
    {
        $r1 = bin2hex(random_bytes(10));
        $r2 = bin2hex(random_bytes(10));
        $r3 = bin2hex(random_bytes(10));
        $r4 = bin2hex(random_bytes(10));
        $token = $r1.'-'.$r2.'-'.$r3.'-'.$r4;
        return $token;
    }
    //Formato para valores monetarios
    function formatMoney($cantidad){
        $cantidad = number_format($cantidad,2,SPD,SPM);
        return $cantidad;
    }
    
    function getTokenPaypal(){
        $payLogin = curl_init(URLPAYPAL."/v1/oauth2/token");//COUNT CARLOSCC
        curl_setopt($payLogin, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($payLogin, CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($payLogin, CURLOPT_USERPWD, IDCLIENTE.":".SECRET);
        curl_setopt($payLogin, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($payLogin);
        $err = curl_error($payLogin);
        curl_close($payLogin);
        if($err){
            $request = "CURL Error #:" . $err;
        }else{
            $objData = json_decode($result);
             $request =  $objData->access_token;
        }
        return $request;
    }

    function CurlConnectionGet(string $ruta, string $contentType = null, string $token){
        $content_type = $contentType != null ? $contentType : "application/x-www-form-urlencoded";
        if($token != null){
            $arrHeader = array('Content-Type:'.$content_type,
                            'Authorization: Bearer '.$token);
        }else{
            $arrHeader = array('Content-Type:'.$content_type);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ruta);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeader);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if($err){
            $request = "CURL Error #:" . $err;
        }else{
            $request = json_decode($result);
        }
        return $request;
    }

    function CurlConnectionPost(string $ruta, string $contentType = null, string $token){
        $content_type = $contentType != null ? $contentType : "application/x-www-form-urlencoded";
        if($token != null){
            $arrHeader = array('Content-Type:'.$content_type,
                            'Authorization: Bearer '.$token);
        }else{
            $arrHeader = array('Content-Type:'.$content_type);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ruta);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeader);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if($err){
            $request = "CURL Error #:" . $err;
        }else{
            $request = json_decode($result);
        }
        return $request;
    }

    function Meses(){
        $meses = array("Enero", 
                      "Febrero", 
                      "Marzo", 
                      "Abril",  
                      "Mayo", 
                      "Junio", 
                      "Julio", 
                      "Agosto", 
                      "Septiembre", 
                      "Octubre", 
                      "Noviembre", 
                      "Diciembre");
        return $meses;
    }

    /**
     * Sanitiza GET
     * @return array    
     */
    function sanitizeGet() {
        $clean = [];
        foreach ($_GET as $key => $value) {
            $clean[$key] = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        return $clean;
    }

    /**
     * Verifica permisos usando las constantes de Config.php
     * @param int $moduleId - La constante (ej: COM_COMPRAS)
     * @param string $action - 'r', 'w', 'u', 'd'
     * @return bool
     */
    function hasPermissions($moduleId, $action = 'r') {
        return !empty($_SESSION['permisos'][$moduleId][$action]) && $_SESSION['permisos'][$moduleId][$action] == 1;
    }


 function looksLikeBase64(string $s): bool
{
    if ($s === '' || strlen($s) < 8) return false;
    // base64 típico: A-Z a-z 0-9 + / =
    return (bool)preg_match('/^[A-Za-z0-9+\/=]+$/', $s);
}









 ?>