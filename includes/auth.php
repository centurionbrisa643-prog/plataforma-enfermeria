<?php
/**
 * Inicio de sesión y control de roles.
 * Roles (F02): admin = Dirección y Coordinación de Prácticas; estudiante.
 */
function usuario_actual(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['usuario_id'])) {
            $u = q('SELECT id, nombre, apellido, email, rol, cargo, sede_id, turno_id
                      FROM usuarios WHERE id = ? AND activo', [$_SESSION['usuario_id']])->fetch() ?: null;
            if (!$u) unset($_SESSION['usuario_id']);
        }
    }
    return $u;
}

function requerir_login(?string $rol = null): array
{
    $u = usuario_actual();
    if (!$u) {
        redirigir('login.php');
    }
    if ($rol && $u['rol'] !== $rol) {
        http_response_code(403);
        flash('error', 'No tenés permiso para entrar a esa sección.');
        redirigir('inicio.php');
    }
    verificar_csrf();
    return $u;
}

function es_admin(): bool
{
    return (usuario_actual()['rol'] ?? '') === 'admin';
}

function intentar_login(string $email, string $clave): bool
{
    // Freno simple contra intentos repetidos
    $_SESSION['intentos'] = $_SESSION['intentos'] ?? 0;
    if ($_SESSION['intentos'] >= 5 && time() - ($_SESSION['ultimo_intento'] ?? 0) < 60) {
        return false;
    }
    $u = q('SELECT id, password_hash FROM usuarios WHERE lower(email) = lower(?) AND activo',
           [trim($email)])->fetch();
    if ($u && password_verify($clave, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $u['id'];
        $_SESSION['intentos'] = 0;
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE usuarios SET password_hash = ? WHERE id = ?',
              [password_hash($clave, PASSWORD_DEFAULT), $u['id']]);
        }
        return true;
    }
    $_SESSION['intentos']++;
    $_SESSION['ultimo_intento'] = time();
    return false;
}

/** Datos académicos del estudiante: semestres visibles y habilitación. */
function datos_estudiante(int $id): array
{
    $e = q('SELECT * FROM v_estudiantes WHERE id = ?', [$id])->fetch();
    $e['semestres'] = q('SELECT s.id, s.numero, s.nombre, a.estado, a.fecha_aprobacion
                           FROM avance_estudiante a JOIN semestres s ON s.id = a.semestre_id
                          WHERE a.estudiante_id = ? ORDER BY s.numero', [$id])->fetchAll();
    return $e;
}
