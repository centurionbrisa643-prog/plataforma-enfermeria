<?php
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();

// ---------------- ADMINISTRADOR ----------------
if ($u['rol'] === 'admin') {
    if (($_POST['accion'] ?? '') === 'enviar_correos') {
        $r = enviar_correos_pendientes(100);
        flash($r['error'] && !$r['enviados'] ? 'error' : 'exito',
              "Correos enviados: {$r['enviados']}. Pendientes: {$r['pendientes']}." . ($r['error'] ? ' Último error: ' . $r['error'] : ''));
        redirigir('inicio.php');
    }
    $c = q("SELECT
              (SELECT COUNT(*) FROM v_estudiantes WHERE activo) AS activos,
              (SELECT COUNT(*) FROM v_estudiantes WHERE activo AND NOT habilitado_practicas) AS sin_habilitar,
              (SELECT COUNT(*) FROM practicas_realizadas WHERE fecha_registro >= date_trunc('month', NOW())) AS practicas_mes,
              (SELECT COUNT(*) FROM notificaciones WHERE NOT correo_enviado AND intentos_correo < 5) AS correos_pend")->fetch();
    $pendientes = q('SELECT * FROM v_estudiantes WHERE activo AND NOT habilitado_practicas ORDER BY apellido LIMIT 6')->fetchAll();
    $eventos = eventos_para($u, date('Y-m-d'), date('Y-m-d', strtotime('+60 days')), 5);

    layout_inicio('Inicio', 'inicio', ['saludo' => true]); ?>
    <div class="cifras">
      <div class="cifra"><b><?= $c['activos'] ?></b><span>Estudiantes activos</span></div>
      <div class="cifra"><b><?= $c['sin_habilitar'] ?></b><span>Sin habilitación para prácticas</span></div>
      <div class="cifra"><b><?= $c['practicas_mes'] ?></b><span>Prácticas registradas este mes</span></div>
      <div class="cifra"><b><?= $c['correos_pend'] ?></b><span>Correos pendientes de envío</span></div>
    </div>

    <div class="seccion-titulo"><h2>Accesos rápidos</h2></div>
    <div class="mosaicos">
      <a class="mosaico" href="admin/estudiantes.php"><?= icono('usuarios') ?>Estudiantes<small>Avance, requisitos y prácticas</small></a>
      <a class="mosaico" href="admin/procedimientos.php"><?= icono('libreta') ?>Libreta<small>Procedimientos por semestre</small></a>
      <a class="mosaico" href="calendario.php"><?= icono('calendario') ?>Calendario<small>Parciales, finales y avisos</small></a>
      <a class="mosaico" href="documentos.php"><?= icono('documento') ?>Documentos<small>Reglamentos, PAE y protocolos</small></a>
    </div>

    <div class="grilla grilla-principal" style="margin-top:22px">
      <section class="tarjeta">
        <div class="tarjeta-titulo"><h3>Próximos eventos</h3><a class="btn btn-suave btn-chico" href="calendario.php"><?= icono('mas') ?>Nuevo</a></div>
        <?php if (!$eventos): ?><div class="vacio"><?= icono('calendario') ?>No hay eventos en los próximos 60 días.</div><?php endif; ?>
        <ul class="lista"><?php foreach ($eventos as $ev) echo fila_evento($ev, true) . '</li>'; ?></ul>
      </section>
      <div>
        <section class="tarjeta">
          <div class="tarjeta-titulo"><h3>Pendientes de habilitación</h3></div>
          <p class="muy-tenue">Falta el curso de bioseguridad o la habilitación ante la DENERS.</p>
          <?php if (!$pendientes): ?><div class="vacio"><?= icono('check_circulo') ?>Todos están habilitados.</div><?php endif; ?>
          <ul class="lista">
          <?php foreach ($pendientes as $p): ?>
            <li><a class="fila-enlace" href="admin/estudiante.php?id=<?= $p['id'] ?>">
              <div class="avatar"><?= e(iniciales($p['nombre'], $p['apellido'])) ?></div>
              <div class="lista-cuerpo"><strong><?= e($p['apellido'] . ', ' . $p['nombre']) ?></strong>
                <small><?= $p['semestre_actual'] ?>.º sem. · <?= e($p['turno']) ?> ·
                  <?= $p['bioseguridad_aprobado'] ? '' : 'Bioseguridad' ?><?= !$p['bioseguridad_aprobado'] && !$p['deners_habilitado'] ? ' y ' : '' ?><?= $p['deners_habilitado'] ? '' : 'DENERS' ?></small></div>
              <?= icono('flecha_der') ?></a></li>
          <?php endforeach; ?>
          </ul>
        </section>
        <section class="tarjeta">
          <div class="tarjeta-titulo"><h3>Correos de aviso</h3></div>
          <p class="muy-tenue">Cada aviso se muestra en la plataforma y también se envía por correo. Si un envío falla, queda pendiente.</p>
          <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="enviar_correos">
            <button class="btn btn-sec btn-bloque" type="submit" <?= $c['correos_pend'] ? '' : 'disabled' ?>><?= icono('enviar') ?>Enviar correos pendientes (<?= $c['correos_pend'] ?>)</button></form>
        </section>
      </div>
    </div>
    <?php
    layout_fin('inicio');
    exit;
}

// ---------------- ESTUDIANTE ----------------
$est = datos_estudiante($u['id']);
$prog = progreso_libreta($u['id']);
$actual = end($est['semestres']) ?: null;
$pa = $actual ? ($prog[$actual['id']] ?? ['total' => 0, 'hechas' => 0]) : ['total' => 0, 'hechas' => 0];
$eventos = eventos_para($u, date('Y-m-d'), date('Y-m-d', strtotime('+45 days')), 4);
$sinLeer = notificaciones_sin_leer($u['id']);

layout_inicio('Inicio', 'inicio', ['saludo' => true]); ?>

<section class="heroe">
  <div class="decoracion"><?= icono('estetoscopio') ?></div>
  <div class="heroe-contenido">
    <h2><?= icono('libreta') ?>Mi Libreta de Prácticas</h2>
    <p>Conocé los procedimientos de tu semestre y seguí tu avance. Tu instructor firma cada práctica en la libreta física.</p>
    <?php if ($actual): ?>
      <div class="progreso" role="progressbar" aria-valuenow="<?= porcentaje($pa['hechas'], $pa['total']) ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width:<?= porcentaje($pa['hechas'], $pa['total']) ?>%"></span></div>
      <div class="progreso-texto"><span><?= e($actual['nombre']) ?> · <?= e($est['turno']) ?></span>
        <span><?= $pa['hechas'] ?> de <?= $pa['total'] ?> realizadas</span></div>
    <?php endif; ?>
    <a class="btn" href="libreta.php" style="margin-top:14px">Ver mi libreta <?= icono('flecha_der') ?></a>
  </div>
</section>

<?php if (!$est['habilitado_practicas']): ?>
  <div class="aviso aviso-alerta" style="margin-top:16px"><?= icono('alerta') ?>
    <span>Todavía no estás habilitado/a para realizar prácticas.
      <?= $est['bioseguridad_aprobado'] ? '' : 'Falta aprobar el curso de bioseguridad. ' ?>
      <?= $est['deners_habilitado'] ? '' : 'Falta la habilitación ante la DENERS.' ?></span></div>
<?php endif; ?>

<div class="seccion-titulo"><h2>Accesos</h2></div>
<div class="mosaicos">
  <a class="mosaico" href="libreta.php"><?= icono('libreta') ?>Procedimientos</a>
  <a class="mosaico" href="calendario.php"><?= icono('calendario') ?>Calendario</a>
  <a class="mosaico" href="documentos.php"><?= icono('documento') ?>Reglamentos y manuales</a>
  <a class="mosaico" href="documentos.php?cat=protocolo_bioseguridad"><?= icono('escudo_check') ?>Bioseguridad</a>
  <a class="mosaico" href="notificaciones.php"><?= icono('campana') ?>Notificaciones<?php if ($sinLeer): ?><small><?= $sinLeer ?> sin leer</small><?php endif; ?></a>
  <a class="mosaico" href="perfil.php#requisitos"><?= icono('birrete') ?>Mi avance y requisitos</a>
  <a class="mosaico" href="documentos.php?cat=pae_hospitalario"><?= icono('hospital') ?>PAE Hospitalario</a>
  <a class="mosaico" href="documentos.php?cat=pae_comunitario"><?= icono('casa') ?>PAE Comunitario</a>
</div>

<section class="tarjeta" style="margin-top:22px">
  <div class="tarjeta-titulo"><h3>Próximas fechas</h3><a href="calendario.php" class="muy-tenue">Ver calendario</a></div>
  <?php if (!$eventos): ?><div class="vacio"><?= icono('calendario') ?>No tenés fechas próximas.</div><?php endif; ?>
  <ul class="lista"><?php foreach ($eventos as $ev) echo fila_evento($ev) . '</li>'; ?></ul>
</section>
<?php layout_fin('inicio');
