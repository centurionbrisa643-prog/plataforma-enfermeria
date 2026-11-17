<?php
/**
 * Reintenta los correos pendientes. Se puede programar en Windows con el
 * "Programador de tareas" cada 10 minutos:
 *   php C:\ruta\plataforma-enfermeria\tareas\enviar_correos.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/notificar.php';
$r = enviar_correos_pendientes(200);
echo date('Y-m-d H:i') . " - enviados: {$r['enviados']}, pendientes: {$r['pendientes']}" . ($r['error'] ? " - error: {$r['error']}" : '') . PHP_EOL;
