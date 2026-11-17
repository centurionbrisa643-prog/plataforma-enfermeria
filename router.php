<?php
/**
 * Solo para el servidor de desarrollo de PHP:
 *   php -S localhost:8000 router.php
 * Bloquea el acceso directo a carpetas internas.
 */
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(config|includes|sql|storage|tareas)(/|$)#', $ruta)) {
    http_response_code(403);
    exit('Acceso denegado');
}
return false; // el resto lo sirve PHP normalmente
