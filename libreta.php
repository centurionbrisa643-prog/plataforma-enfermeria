<?php
/**
 * Libreta Digital de Prácticas — CONSULTA (F01 + F02).
 * El estudiante no carga datos. Ve los procedimientos de sus semestres
 * habilitados y cuáles ya fueron registrados como realizados (DG).
 */
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login('estudiante');

$est = datos_estudiante($u['id']);
$visibles = array_column($est['semestres'], null, 'id');
$todos = q('SELECT id, numero, nombre FROM semestres ORDER BY numero')->fetchAll();
$prog = progreso_libreta($u['id']);

$semId = (int)($_GET['sem'] ?? 0);
if (!isset($visibles[$semId])) {
    $ultimo = end($est['semestres']);
    $semId = $ultimo['id'] ?? 0;
}
$sem = $visibles[$semId] ?? null;

$procs = $sem ? q('SELECT p.*, r.fecha_realizacion, r.instructor
                     FROM procedimientos p
                     LEFT JOIN practicas_realizadas r ON r.procedimiento_id = p.id AND r.estudiante_id = ?
                    WHERE p.semestre_id = ? AND p.activo
                    ORDER BY p.area, p.titulo', [$u['id'], $semId])->fetchAll() : [];
$pg = $prog[$semId] ?? ['total' => 0, 'hechas' => 0];

layout_inicio('Mi Libreta', 'libreta'); ?>

<nav class="pestanas" aria-label="Semestres">
  <?php foreach ($todos as $s):
    $ok = isset($visibles[$s['id']]); ?>
    <?php if ($ok): ?>
      <a class="pestana <?= $s['id'] == $semId ? 'activa' : '' ?>" href="?sem=<?= $s['id'] ?>">
        <?= $visibles[$s['id']]['estado'] === 'aprobado' ? icono('check') : '' ?><?= e($s['nombre']) ?></a>
    <?php else: ?>
      <span class="pestana bloqueada" title="Se habilita al aprobar el semestre anterior"><?= icono('candado') ?><?= e($s['nombre']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>

<?php if (!$sem): ?>
  <div class="tarjeta bloqueo"><?= icono('candado') ?><h3>Todavía no tenés semestres habilitados</h3>
    <p class="tenue">La Coordinación de Prácticas debe registrar tu semestre actual.</p></div>
<?php else: ?>
  <div class="grilla grilla-principal">
    <div>
      <?php if (!$est['habilitado_practicas']): ?>
        <div class="aviso aviso-alerta"><?= icono('alerta') ?><span>Podés consultar los procedimientos, pero para realizar prácticas necesitás
          el curso de bioseguridad aprobado y la habilitación ante la DENERS.</span></div>
      <?php endif; ?>
      <?php if (!$procs): ?>
        <div class="tarjeta vacio"><?= icono('libreta') ?>Aún no se cargaron procedimientos para este semestre.</div>
      <?php endif;
      $area = null;
      foreach ($procs as $p):
        if ($p['area'] !== $area): $area = $p['area']; ?>
          <h3 style="margin:<?= $p === $procs[0] ? '0' : '22px' ?> 0 10px"><?= e($area) ?></h3>
        <?php endif; ?>
        <a class="procedimiento" href="procedimiento.php?id=<?= $p['id'] ?>">
          <div class="icono-circulo <?= $p['fecha_realizacion'] ? 'hecho' : '' ?>"><?= icono($p['fecha_realizacion'] ? 'check' : 'pulso') ?></div>
          <div class="lista-cuerpo">
            <strong><?= e($p['titulo']) ?></strong>
            <?php if ($p['fecha_realizacion']): ?>
              <small>Realizada el <?= fecha_corta($p['fecha_realizacion']) ?> · firmó <?= e($p['instructor']) ?></small>
            <?php else: ?>
              <small><?= e(mb_strimwidth($p['descripcion'] ?? '', 0, 90, '…')) ?></small>
            <?php endif; ?>
          </div>
          <?php if ($p['fecha_realizacion']): ?>
            <span class="etiqueta-estado e-exito"><?= icono('check') ?>Realizada</span>
          <?php else: ?>
            <span class="etiqueta-estado e-neutro">Pendiente</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <aside class="tarjeta">
      <h3><?= e($sem['nombre']) ?></h3>
      <p class="muy-tenue"><?= $sem['estado'] === 'aprobado'
          ? 'Semestre aprobado el ' . fecha_corta($sem['fecha_aprobacion']) . '.'
          : 'Semestre en curso · ' . e($est['turno']) . ' · ' . e($est['sede']) ?></p>
      <div class="progreso"><span style="width:<?= porcentaje($pg['hechas'], $pg['total']) ?>%"></span></div>
      <div class="progreso-texto"><span><?= porcentaje($pg['hechas'], $pg['total']) ?>% completado</span><span><?= $pg['hechas'] ?>/<?= $pg['total'] ?></span></div>
      <hr style="border:0;border-top:1px solid var(--borde);margin:18px 0">
      <p class="muy-tenue" style="margin:0"><?= icono('editar', 'ico') ?>
        La firma de cada práctica la coloca tu instructor en la libreta física. La Coordinación la registra luego en el sistema.
        Si hubo un error, se corrige de forma presencial.</p>
    </aside>
  </div>
<?php endif;
layout_fin('libreta');
