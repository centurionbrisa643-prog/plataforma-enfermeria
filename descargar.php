<?php
/** Entrega el PDF solo a usuarios con sesión iniciada. */
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();

$d = q('SELECT * FROM documentos WHERE id = ?' . ($u['rol'] === 'admin' ? '' : ' AND publicado'),
       [(int)($_GET['id'] ?? 0)])->fetch();
$ruta = $d && $d['archivo'] ? DIR_DOCUMENTOS . '/' . basename($d['archivo']) : null;
if (!$ruta || !is_file($ruta)) {
    http_response_code(404);
    exit('Documento no disponible.');
}
$nombre = preg_replace('/[^\w\-. áéíóúñÁÉÍÓÚÑ]/u', '_', $d['nombre_original'] ?: $d['titulo'] . '.pdf');
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="' . $nombre . '"');
header('X-Content-Type-Options: nosniff');
readfile($ruta);
