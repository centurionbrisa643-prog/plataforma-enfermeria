<?php
/** Escapa texto para mostrarlo en HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Ruta relativa a la raíz del proyecto (funciona desde /admin también). */
function url(string $ruta = ''): string
{
    $base = defined('EN_ADMIN') ? '../' : '';
    return $base . ltrim($ruta, '/');
}

function redirigir(string $ruta): never
{
    header('Location: ' . url($ruta));
    exit;
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---- Protección CSRF para todos los formularios ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verificar_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('La sesión del formulario expiró. Volvé atrás y recargá la página.');
    }
}

// ---- Fechas en español ----
const MESES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
               'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const MESES_CORTOS = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

function fecha_larga(?string $f, bool $conHora = false): string
{
    if (!$f) return '—';
    $t = strtotime($f);
    $txt = DIAS[(int)date('w', $t)] . ' ' . date('j', $t) . ' de ' . MESES[(int)date('n', $t)];
    if (date('Y', $t) !== date('Y')) $txt .= ' de ' . date('Y', $t);
    if ($conHora && date('H:i', $t) !== '00:00') $txt .= ', ' . date('H:i', $t) . ' h';
    return $txt;
}

function fecha_corta(?string $f): string
{
    if (!$f) return '—';
    $t = strtotime($f);
    return date('j', $t) . ' ' . MESES_CORTOS[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function hace(string $f): string
{
    $s = time() - strtotime($f);
    if ($s < 60) return 'hace un momento';
    if ($s < 3600) return 'hace ' . floor($s / 60) . ' min';
    if ($s < 86400) return 'hace ' . floor($s / 3600) . ' h';
    if ($s < 604800) return 'hace ' . floor($s / 86400) . ' d';
    return fecha_corta($f);
}

const TIPOS_EVENTO = [
    'parcial' => 'Examen parcial', 'final' => 'Examen final', 'practica' => 'Práctica',
    'extension' => 'Extensión', 'investigacion' => 'Investigación', 'otro' => 'Otro',
];

const CATEGORIAS_DOC = [
    'reglamento_practicas'   => ['Reglamento de Prácticas Externas', 'escudo'],
    'reglamento_simulacion'  => ['Reglamento de Sala de Simulación', 'monitor'],
    'pae_hospitalario'       => ['Manual PAE Hospitalario', 'hospital'],
    'pae_comunitario'        => ['Manual PAE Comunitario', 'casa'],
    'guia_procedimientos'    => ['Guías de Procedimientos', 'libro'],
    'protocolo_bioseguridad' => ['Protocolos de Bioseguridad', 'escudo_check'],
];

function iniciales(string $nombre, string $apellido = ''): string
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
}
