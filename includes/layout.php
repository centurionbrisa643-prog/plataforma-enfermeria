<?php
/**
 * Estructura común: barra lateral (escritorio), barra superior y
 * navegación inferior (celular), selector de modo claro/oscuro.
 */
function menu_items(): array
{
    if (es_admin()) {
        return [
            'inicio'         => ['Inicio', 'inicio', 'inicio.php'],
            'estudiantes'    => ['Estudiantes', 'usuarios', 'admin/estudiantes.php'],
            'procedimientos' => ['Libreta', 'libreta', 'admin/procedimientos.php'],
            'calendario'     => ['Calendario', 'calendario', 'calendario.php'],
            'documentos'     => ['Documentos', 'documento', 'documentos.php'],
        ];
    }
    return [
        'inicio'     => ['Inicio', 'inicio', 'inicio.php'],
        'libreta'    => ['Mi Libreta', 'libreta', 'libreta.php'],
        'calendario' => ['Calendario', 'calendario', 'calendario.php'],
        'documentos' => ['Documentos', 'documento', 'documentos.php'],
        'perfil'     => ['Perfil', 'usuario', 'perfil.php'],
    ];
}

function cabeza_html(string $titulo): void
{ ?>
<!DOCTYPE html>
<html lang="es" data-tema="claro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($titulo) ?> · Enfermería UniNorte</title>
  <meta name="theme-color" content="#4B5320">
  <link rel="icon" type="image/png" href="<?= url('assets/img/logo-enfermeria.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>?v=1">
  <script>
    // Aplica el modo guardado (o el del sistema) antes de dibujar la página
    (function () {
      var t = null;
      try { t = localStorage.getItem('tema'); } catch (e) {}
      if (!t) t = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'oscuro' : 'claro';
      document.documentElement.setAttribute('data-tema', t);
    })();
  </script>
</head>
<?php }

function boton_tema(): string
{
    return '<button class="btn-icono boton-tema" type="button" data-alternar-tema aria-label="Cambiar modo claro u oscuro">'
         . icono('luna', 'ico ico-luna') . icono('sol', 'ico ico-sol') . '</button>';
}

function layout_inicio(string $titulo, string $activo = '', array $opc = []): void
{
    $u = usuario_actual();
    $sinLeer = notificaciones_sin_leer($u['id']);
    cabeza_html($titulo);
    $items = menu_items(); ?>
<body class="app">
<a class="saltar" href="#contenido">Saltar al contenido</a>

<aside class="lateral" aria-label="Menú principal">
  <a class="lateral-marca" href="<?= url('inicio.php') ?>">
    <span class="logo-insignia"><img src="<?= url('assets/img/logo-enfermeria.png') ?>" alt="Logo de la Carrera de Enfermería UniNorte"></span>
    <span><strong>Libreta de Prácticas</strong><small>Enfermería · UniNorte</small></span>
  </a>
  <nav class="lateral-nav">
    <?php foreach ($items as $clave => [$txt, $ico, $ruta]): ?>
      <a href="<?= url($ruta) ?>" class="<?= $clave === $activo ? 'activo' : '' ?>"
         <?= $clave === $activo ? 'aria-current="page"' : '' ?>><?= icono($ico) ?><span><?= e($txt) ?></span></a>
    <?php endforeach; ?>
    <a href="<?= url('notificaciones.php') ?>" class="<?= $activo === 'notificaciones' ? 'activo' : '' ?>">
      <?= icono('campana') ?><span>Notificaciones</span><?php if ($sinLeer): ?><b class="contador"><?= $sinLeer ?></b><?php endif; ?></a>
    <?php if (es_admin()): ?>
      <a href="<?= url('perfil.php') ?>" class="<?= $activo === 'perfil' ? 'activo' : '' ?>"><?= icono('usuario') ?><span>Mi perfil</span></a>
    <?php endif; ?>
  </nav>
  <div class="lateral-pie">
    <div class="avatar"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></div>
    <div class="lateral-usuario"><strong><?= e($u['nombre'] . ' ' . $u['apellido']) ?></strong>
      <small><?= e($u['rol'] === 'admin' ? ($u['cargo'] ?: 'Administrador') : 'Estudiante de Enfermería') ?></small></div>
    <a class="btn-icono" href="<?= url('logout.php') ?>" title="Cerrar sesión" aria-label="Cerrar sesión"><?= icono('salir') ?></a>
  </div>
</aside>

<div class="marco">
  <header class="barra <?= !empty($opc['saludo']) ? 'barra-saludo' : '' ?>">
    <?php if (!empty($opc['volver'])): ?>
      <a class="btn-icono solo-movil" href="<?= url($opc['volver']) ?>" aria-label="Volver"><?= icono('flecha_izq') ?></a>
    <?php endif; ?>
    <?php if (!empty($opc['saludo'])): ?>
      <div class="saludo">
        <div class="avatar avatar-grande"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></div>
        <div><h1>¡Hola, <?= e($u['nombre']) ?>! 👋</h1>
          <p><?= e($u['rol'] === 'admin' ? ($u['cargo'] ?: 'Administrador') : 'Estudiante de Enfermería') ?></p></div>
      </div>
    <?php else: ?>
      <h1 class="barra-titulo"><?= e($titulo) ?></h1>
    <?php endif; ?>
    <div class="barra-acciones">
      <?= boton_tema() ?>
      <a class="btn-icono campana" href="<?= url('notificaciones.php') ?>" aria-label="Notificaciones (<?= $sinLeer ?> sin leer)">
        <?= icono('campana') ?><?php if ($sinLeer): ?><b class="punto"><?= $sinLeer > 9 ? '9+' : $sinLeer ?></b><?php endif; ?></a>
      <a class="btn-icono solo-movil" href="<?= url('logout.php') ?>" aria-label="Cerrar sesión"><?= icono('salir') ?></a>
    </div>
  </header>

  <main id="contenido" class="contenido">
    <?php foreach (flashes() as $f): ?>
      <div class="aviso aviso-<?= e($f['tipo']) ?>" role="status"><?= icono($f['tipo'] === 'error' ? 'alerta' : 'check_circulo') ?><span><?= e($f['mensaje']) ?></span></div>
    <?php endforeach;
}

function layout_fin(string $activo = ''): void
{ ?>
  </main>
</div>

<nav class="nav-inferior" aria-label="Navegación">
  <?php foreach (menu_items() as $clave => [$txt, $ico, $ruta]): ?>
    <a href="<?= url($ruta) ?>" class="<?= $clave === $activo ? 'activo' : '' ?>"><?= icono($ico) ?><span><?= e($txt) ?></span></a>
  <?php endforeach; ?>
</nav>
<script src="<?= url('assets/js/app.js') ?>?v=1"></script>
</body>
</html>
<?php }
