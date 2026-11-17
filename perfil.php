<?php
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();

if (($_POST['accion'] ?? '') === 'clave') {
    $actual = q('SELECT password_hash FROM usuarios WHERE id = ?', [$u['id']])->fetchColumn();
    $nueva = $_POST['nueva'] ?? '';
    if (!password_verify($_POST['actual'] ?? '', $actual)) {
        flash('error', 'La contraseña actual no es correcta.');
    } elseif (strlen($nueva) < 8 || $nueva !== ($_POST['repetir'] ?? '')) {
        flash('error', 'La nueva contraseña debe tener al menos 8 caracteres y coincidir en ambos campos.');
    } else {
        q('UPDATE usuarios SET password_hash = ? WHERE id = ?', [password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);
        flash('exito', 'Contraseña actualizada.');
    }
    redirigir('perfil.php');
}

$est = $u['rol'] === 'estudiante' ? datos_estudiante($u['id']) : null;
layout_inicio('Mi perfil', 'perfil'); ?>

<div class="grilla grilla-2">
  <section class="tarjeta">
    <div style="display:flex;gap:14px;align-items:center;margin-bottom:16px">
      <div class="avatar avatar-grande" style="background:var(--musgo-200)"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></div>
      <div><h2 style="margin:0"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></h2>
        <small><?= e($u['rol'] === 'admin' ? ($u['cargo'] ?: 'Administrador') : 'Estudiante de Enfermería') ?></small></div>
    </div>
    <ul class="lista">
      <li><?= icono('correo') ?><div class="lista-cuerpo"><small>Correo (recibe las notificaciones)</small><strong><?= e($u['email']) ?></strong></div></li>
      <?php if ($est): ?>
        <li><?= icono('hospital') ?><div class="lista-cuerpo"><small>Sede · Turno</small><strong><?= e($est['sede']) ?> · <?= e($est['turno']) ?></strong></div></li>
        <li><?= icono('birrete') ?><div class="lista-cuerpo"><small>Semestre actual</small><strong><?= (int)$est['semestre_actual'] ?>.º semestre</strong></div></li>
      <?php endif; ?>
    </ul>
    <p class="muy-tenue" style="margin-top:10px">Para corregir tus datos, comunicate con la Coordinación de Prácticas.</p>
  </section>

  <?php if ($est): ?>
  <section class="tarjeta" id="requisitos">
    <h3>Requisitos para prácticas</h3>
    <ul class="lista">
      <li><div class="icono-circulo <?= $est['bioseguridad_aprobado'] ? 'hecho' : '' ?>"><?= icono('escudo_check') ?></div>
        <div class="lista-cuerpo"><strong>Curso de bioseguridad</strong><small>Requisito institucional</small></div>
        <?= $est['bioseguridad_aprobado'] ? '<span class="etiqueta-estado e-exito">' . icono('check') . 'Aprobado</span>' : '<span class="etiqueta-estado e-alerta">Pendiente</span>' ?></li>
      <li><div class="icono-circulo <?= $est['deners_habilitado'] ? 'hecho' : '' ?>"><?= icono('escudo') ?></div>
        <div class="lista-cuerpo"><strong>Habilitación DENERS</strong><small>Requisito para realizar prácticas</small></div>
        <?= $est['deners_habilitado'] ? '<span class="etiqueta-estado e-exito">' . icono('check') . 'Habilitado</span>' : '<span class="etiqueta-estado e-alerta">Pendiente</span>' ?></li>
    </ul>
    <h3 style="margin-top:18px">Mi avance</h3>
    <ul class="lista">
      <?php foreach ($est['semestres'] as $s): ?>
        <li><div class="icono-circulo <?= $s['estado'] === 'aprobado' ? 'hecho' : '' ?>"><?= icono($s['estado'] === 'aprobado' ? 'check' : 'reloj') ?></div>
          <div class="lista-cuerpo"><strong><?= e($s['nombre']) ?></strong>
            <small><?= $s['estado'] === 'aprobado' ? 'Aprobado el ' . fecha_corta($s['fecha_aprobacion']) : 'En curso' ?></small></div></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="tarjeta">
    <h3>Cambiar contraseña</h3>
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="clave">
      <div class="campo"><label for="a">Contraseña actual</label><input id="a" name="actual" type="password" autocomplete="current-password" required></div>
      <div class="campo"><label for="n">Nueva contraseña</label><input id="n" name="nueva" type="password" autocomplete="new-password" minlength="8" required>
        <span class="ayuda">Mínimo 8 caracteres.</span></div>
      <div class="campo"><label for="r">Repetir nueva contraseña</label><input id="r" name="repetir" type="password" autocomplete="new-password" minlength="8" required></div>
      <button class="btn" type="submit"><?= icono('candado') ?>Actualizar contraseña</button>
    </form>
  </section>
</div>
<?php layout_fin('perfil');
