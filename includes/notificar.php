<?php
/**
 * Notificación doble (decisión del grupo):
 *   1) queda guardada en la plataforma (campanita)
 *   2) se envía por correo; si falla, queda pendiente para reintentar.
 */
require_once __DIR__ . '/smtp.php';

/**
 * Crea la notificación para cada usuario y trata de enviar los correos.
 * Devuelve ['creadas' => n, 'enviados' => n, 'pendientes' => n, 'error' => ?string]
 */
function notificar(array $usuarioIds, string $titulo, string $mensaje, ?string $enlace = null): array
{
    $usuarioIds = array_values(array_unique(array_map('intval', $usuarioIds)));
    if (!$usuarioIds) return ['creadas' => 0, 'enviados' => 0, 'pendientes' => 0, 'error' => null];

    $st = db()->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, enlace) VALUES (?, ?, ?, ?)');
    db()->beginTransaction();
    foreach ($usuarioIds as $id) {
        $st->execute([$id, $titulo, $mensaje, $enlace]);
    }
    db()->commit();

    $r = enviar_correos_pendientes(CORREOS_POR_LOTE);
    $r['creadas'] = count($usuarioIds);
    return $r;
}

/** Envía correos pendientes (también lo usa el botón del admin y tareas/enviar_correos.php). */
function enviar_correos_pendientes(int $limite = 50): array
{
    $pendientesTotal = fn() => (int)q('SELECT COUNT(*) FROM notificaciones
                                        WHERE NOT correo_enviado AND intentos_correo < 5')->fetchColumn();
    if (!SMTP_HABILITADO) {
        return ['enviados' => 0, 'pendientes' => $pendientesTotal(), 'error' => 'El envío de correos todavía no está configurado (config.php).'];
    }

    $lote = q('SELECT n.id, n.titulo, n.mensaje, n.enlace, u.email, u.nombre, u.apellido
                 FROM notificaciones n JOIN usuarios u ON u.id = n.usuario_id
                WHERE NOT n.correo_enviado AND n.intentos_correo < 5 AND u.activo
                ORDER BY n.fecha LIMIT ?', [$limite])->fetchAll();
    if (!$lote) return ['enviados' => 0, 'pendientes' => $pendientesTotal(), 'error' => null];

    @set_time_limit(300);
    $smtp = new ClienteSmtp(SMTP_HOST, SMTP_PUERTO, SMTP_SEGURIDAD, SMTP_USUARIO, SMTP_CLAVE,
                            SMTP_REMITENTE, SMTP_NOMBRE_REMITENTE, SMTP_VERIFICAR_CERT);
    $stOk  = db()->prepare('UPDATE notificaciones SET correo_enviado = TRUE, fecha_envio_correo = NOW(),
                                   error_correo = NULL, intentos_correo = intentos_correo + 1 WHERE id = ?');
    $stErr = db()->prepare('UPDATE notificaciones SET intentos_correo = intentos_correo + 1,
                                   error_correo = ? WHERE id = ?');
    $enviados = 0;
    $error = null;
    try {
        $smtp->conectar();
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
        foreach ($lote as $n) $stErr->execute([$error, $n['id']]);
        return ['enviados' => 0, 'pendientes' => $pendientesTotal(), 'error' => $error];
    }
    foreach ($lote as $n) {
        $nombre = trim($n['nombre'] . ' ' . $n['apellido']);
        try {
            $smtp->enviar($n['email'], $nombre, $n['titulo'],
                correo_html($nombre, $n['titulo'], $n['mensaje'], $n['enlace']),
                correo_texto($nombre, $n['titulo'], $n['mensaje'], $n['enlace']));
            $stOk->execute([$n['id']]);
            $enviados++;
        } catch (Throwable $ex) {
            $error = $ex->getMessage();
            $stErr->execute([$error, $n['id']]);
            $smtp->reiniciar();
        }
    }
    $smtp->cerrar();
    return ['enviados' => $enviados, 'pendientes' => $pendientesTotal(), 'error' => $error];
}

/** Texto para mostrar al admin después de notificar. */
function resumen_notificacion(array $r): string
{
    $t = "Se notificó en la plataforma a {$r['creadas']} usuario(s).";
    if ($r['enviados']) $t .= " Correos enviados: {$r['enviados']}.";
    if ($r['pendientes']) $t .= " Correos pendientes: {$r['pendientes']}.";
    return $t;
}

function correo_texto(string $nombre, string $titulo, string $mensaje, ?string $enlace): string
{
    return "Hola, $nombre:\n\n$titulo\n\n$mensaje\n\n"
         . ($enlace ? 'Ver en la plataforma: ' . APP_URL . '/' . $enlace . "\n\n" : '')
         . "Carrera de Enfermería - Universidad del Norte\nEste es un aviso automático.";
}

function correo_html(string $nombre, string $titulo, string $mensaje, ?string $enlace): string
{
    $boton = $enlace
        ? '<p style="margin:28px 0 8px"><a href="' . e(APP_URL . '/' . $enlace) . '" style="background:#556B2F;color:#fff;
             text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;display:inline-block">Ver en la plataforma</a></p>'
        : '';
    return '<!DOCTYPE html><html lang="es"><body style="margin:0;background:#F3F4EC;font-family:Segoe UI,Arial,sans-serif;color:#262B1C">
      <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px 12px"><tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:16px;overflow:hidden">
          <tr><td style="background:#4B5320;color:#fff;padding:20px 28px;font-size:18px;font-weight:700">
            Enfermería · UniNorte</td></tr>
          <tr><td style="padding:28px">
            <p style="margin:0 0 12px;color:#6B7259">Hola, ' . e($nombre) . ':</p>
            <h1 style="margin:0 0 12px;font-size:20px;color:#3F4A1F">' . e($titulo) . '</h1>
            <p style="margin:0;line-height:1.6">' . nl2br(e($mensaje)) . '</p>' . $boton . '
          </td></tr>
          <tr><td style="padding:16px 28px;background:#F7F8F2;font-size:12px;color:#6B7259">
            Aviso automático de la Plataforma Académica de la Carrera de Enfermería. Este mensaje también figura en tus notificaciones.</td></tr>
        </table></td></tr></table></body></html>';
}

/** Estudiantes activos alcanzados por un evento (NULL = todos). */
function destinatarios_evento(?int $sede, ?int $turno, ?int $semestre): array
{
    return q('SELECT v.id FROM v_estudiantes v
               WHERE v.activo
                 AND (?::int IS NULL OR v.sede_id = ?)
                 AND (?::int IS NULL OR v.turno_id = ?)
                 AND (?::int IS NULL OR v.semestre_actual = (SELECT numero FROM semestres WHERE id = ?))',
             [$sede, $sede, $turno, $turno, $semestre, $semestre])->fetchAll(PDO::FETCH_COLUMN);
}

function notificaciones_sin_leer(int $usuarioId): int
{
    return (int)q('SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND NOT leida', [$usuarioId])->fetchColumn();
}
