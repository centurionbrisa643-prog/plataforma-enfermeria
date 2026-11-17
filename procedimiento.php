<?php
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();

$p = q('SELECT p.*, s.nombre AS semestre FROM procedimientos p JOIN semestres s ON s.id = p.semestre_id
         WHERE p.id = ?', [(int)($_GET['id'] ?? 0)])->fetch();

// El estudiante solo ve procedimientos de semestres habilitados (F02)
$realizada = null;
if ($p && $u['rol'] === 'estudiante') {
    $visible = q('SELECT 1 FROM avance_estudiante WHERE estudiante_id = ? AND semestre_id = ?',
                 [$u['id'], $p['semestre_id']])->fetchColumn();
    if (!$visible || !$p['activo']) $p = null;
    else $realizada = q('SELECT * FROM practicas_realizadas WHERE estudiante_id = ? AND procedimiento_id = ?',
                        [$u['id'], $p['id']])->fetch();
}
if (!$p) {
    flash('error', 'Ese procedimiento no está disponible.');
    redirigir($u['rol'] === 'admin' ? 'admin/procedimientos.php' : 'libreta.php');
}

$volver = $u['rol'] === 'admin' ? 'admin/procedimientos.php' : 'libreta.php?sem=' . $p['semestre_id'];
layout_inicio('Procedimiento', $u['rol'] === 'admin' ? 'procedimientos' : 'libreta', ['volver' => $volver]); ?>

<a href="<?= e($volver) ?>" class="muy-tenue" style="display:inline-flex;gap:4px;align-items:center;margin-bottom:12px"><?= icono('flecha_izq') ?>Volver</a>

<div class="grilla grilla-principal">
  <article class="tarjeta">
    <span class="area-etiqueta"><?= e($p['semestre']) ?> · <?= e($p['area']) ?></span>
    <h2 style="margin-top:6px"><?= e($p['titulo']) ?></h2>
    <?php if ($p['descripcion']): ?><p class="tenue"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>
    <?php $pasos = array_filter(array_map('trim', explode("\n", (string)$p['pasos']))); ?>
    <?php if ($pasos): ?>
      <h3 style="margin-top:18px">Pasos</h3>
      <ol class="pasos"><?php foreach ($pasos as $paso): ?><li><?= e($paso) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
  </article>

  <?php if ($u['rol'] === 'estudiante'): ?>
  <aside class="tarjeta">
    <h3>Estado</h3>
    <?php if ($realizada): ?>
      <div class="aviso aviso-exito"><?= icono('check_circulo') ?><span>Práctica realizada</span></div>
      <p><small>Fecha</small><br><strong><?= fecha_larga($realizada['fecha_realizacion']) ?></strong></p>
      <p><small>Firmó en la libreta física</small><br><strong><?= e($realizada['instructor']) ?></strong></p>
      <p class="muy-tenue">Registrado por la Coordinación el <?= fecha_corta($realizada['fecha_registro']) ?>.</p>
    <?php else: ?>
      <div class="aviso aviso-info"><?= icono('reloj') ?><span>Pendiente de realizar</span></div>
      <p class="muy-tenue">Cuando realices esta práctica, tu instructor la firmará en la libreta física y la Coordinación la marcará aquí.</p>
    <?php endif; ?>
  </aside>
  <?php endif; ?>
</div>
<?php layout_fin($u['rol'] === 'admin' ? 'procedimientos' : 'libreta');
