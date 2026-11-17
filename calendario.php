<?php
/**
 * Calendario académico (F01) con parciales, finales y avisos de cambios (F02).
 * El admin crea, modifica o elimina eventos; cada cambio genera la
 * notificación doble a los estudiantes alcanzados (DG).
 */
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();
$admin = $u['rol'] === 'admin';

// ---------------- Acciones del administrador ----------------
if ($admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nulo = fn($v) => ($v === '' || $v === null) ? null : (int)$v;

    if ($accion === 'guardar') {
        $d = [
            'titulo'       => trim($_POST['titulo'] ?? ''),
            'tipo'         => $_POST['tipo'] ?? 'otro',
            'descripcion'  => trim($_POST['descripcion'] ?? '') ?: null,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin'    => ($_POST['fecha_fin'] ?? '') ?: null,
            'sede_id'      => $nulo($_POST['sede_id'] ?? ''),
            'turno_id'     => $nulo($_POST['turno_id'] ?? ''),
            'semestre_id'  => $nulo($_POST['semestre_id'] ?? ''),
        ];
        if ($d['titulo'] === '' || !$d['fecha_inicio'] || !isset(TIPOS_EVENTO[$d['tipo']])) {
            flash('error', 'Completá el título, el tipo y la fecha de inicio.');
            redirigir('calendario.php' . ($id ? '?editar=' . $id : ''));
        }
        if ($d['fecha_fin'] && $d['fecha_fin'] < $d['fecha_inicio']) {
            flash('error', 'La fecha de fin no puede ser anterior al inicio.');
            redirigir('calendario.php' . ($id ? '?editar=' . $id : ''));
        }
        $dest = destinatarios_evento($d['sede_id'], $d['turno_id'], $d['semestre_id']);
        if ($id) {
            $antes = q('SELECT * FROM eventos_calendario WHERE id = ?', [$id])->fetch();
            q('UPDATE eventos_calendario SET titulo=?, tipo=?, descripcion=?, fecha_inicio=?, fecha_fin=?,
                      sede_id=?, turno_id=?, semestre_id=?, actualizado_en=NOW() WHERE id=?',
              [...array_values($d), $id]);
            $dest = array_merge($dest, destinatarios_evento($antes['sede_id'], $antes['turno_id'], $antes['semestre_id']));
            $cambios = [];
            if (strtotime($antes['fecha_inicio']) !== strtotime($d['fecha_inicio']))
                $cambios[] = 'Nueva fecha: ' . fecha_larga($d['fecha_inicio'], true) . ' (antes: ' . fecha_larga($antes['fecha_inicio'], true) . ').';
            if ($antes['titulo'] !== $d['titulo']) $cambios[] = 'Antes se llamaba: «' . $antes['titulo'] . '».';
            if (($antes['descripcion'] ?? '') !== ($d['descripcion'] ?? '')) $cambios[] = 'Se actualizó la descripción.';
            $msg = TIPOS_EVENTO[$d['tipo']] . ': «' . $d['titulo'] . '». ' . ($cambios ? implode(' ', $cambios) : 'Se modificaron los datos del evento.');
            $r = notificar($dest, 'Cambio en el calendario', $msg, 'calendario.php?m=' . substr($d['fecha_inicio'], 0, 7));
            flash('exito', 'Evento actualizado. ' . resumen_notificacion($r));
        } else {
            q('INSERT INTO eventos_calendario (titulo, tipo, descripcion, fecha_inicio, fecha_fin, sede_id, turno_id, semestre_id, creado_por)
               VALUES (?,?,?,?,?,?,?,?,?)', [...array_values($d), $u['id']]);
            if (!empty($_POST['notificar'])) {
                $r = notificar($dest, 'Nueva fecha en el calendario',
                    TIPOS_EVENTO[$d['tipo']] . ': «' . $d['titulo'] . '», ' . fecha_larga($d['fecha_inicio'], true) . '.',
                    'calendario.php?m=' . substr($d['fecha_inicio'], 0, 7));
                flash('exito', 'Evento creado. ' . resumen_notificacion($r));
            } else {
                flash('exito', 'Evento creado sin notificar.');
            }
        }
        redirigir('calendario.php?m=' . substr($d['fecha_inicio'], 0, 7));
    }

    if ($accion === 'eliminar' && $id) {
        $ev = q('SELECT * FROM eventos_calendario WHERE id = ?', [$id])->fetch();
        if ($ev) {
            q('DELETE FROM eventos_calendario WHERE id = ?', [$id]);
            $r = notificar(destinatarios_evento($ev['sede_id'], $ev['turno_id'], $ev['semestre_id']),
                'Evento cancelado', TIPOS_EVENTO[$ev['tipo']] . ': «' . $ev['titulo'] . '» del ' . fecha_larga($ev['fecha_inicio'], true) . ' fue cancelado.',
                'calendario.php');
            flash('exito', 'Evento eliminado. ' . resumen_notificacion($r));
        }
        redirigir('calendario.php');
    }
}

// ---------------- Datos del mes ----------------
$m = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');
$inicioMes = strtotime($m . '-01');
$finMes = strtotime('+1 month', $inicioMes);
$eventos = eventos_para($u, date('Y-m-d', $inicioMes), date('Y-m-d', $finMes));
$porDia = [];
foreach ($eventos as $ev) {
    $d = max(strtotime(date('Y-m-d', strtotime($ev['fecha_inicio']))), $inicioMes);
    $fin = min(strtotime(date('Y-m-d', strtotime($ev['fecha_fin'] ?: $ev['fecha_inicio']))), $finMes - 1);
    for (; $d <= $fin; $d = strtotime('+1 day', $d)) $porDia[date('j', $d)][] = $ev['tipo'];
}
$editar = $admin && isset($_GET['editar'])
    ? q('SELECT * FROM eventos_calendario WHERE id = ?', [(int)$_GET['editar']])->fetch() : null;

layout_inicio('Calendario académico', 'calendario'); ?>

<div class="grilla grilla-principal">
  <section class="tarjeta">
    <div class="tarjeta-titulo">
      <a class="btn-icono" href="?m=<?= date('Y-m', strtotime('-1 month', $inicioMes)) ?>" aria-label="Mes anterior"><?= icono('flecha_izq') ?></a>
      <h2 style="text-transform:capitalize"><?= MESES[(int)date('n', $inicioMes)] . ' ' . date('Y', $inicioMes) ?></h2>
      <a class="btn-icono" href="?m=<?= date('Y-m', $finMes) ?>" aria-label="Mes siguiente"><?= icono('flecha_der') ?></a>
    </div>
    <div class="mes" role="grid" aria-label="Días del mes">
      <?php foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'] as $dn): ?><div class="dia-nombre"><?= $dn ?></div><?php endforeach; ?>
      <?php
      $vacios = ((int)date('N', $inicioMes)) - 1;
      for ($i = 0; $i < $vacios; $i++) echo '<div class="dia fuera"></div>';
      $diasMes = (int)date('t', $inicioMes);
      for ($dia = 1; $dia <= $diasMes; $dia++):
        $hoy = date('Y-m-j') === date('Y-m-', $inicioMes) . $dia; ?>
        <div class="dia <?= $hoy ? 'hoy' : '' ?>"><?= $dia ?>
          <?php if (!empty($porDia[$dia])): ?><div class="marcas"><?php foreach (array_unique($porDia[$dia]) as $t): ?><i class="t-<?= $t ?>" title="<?= e(TIPOS_EVENTO[$t]) ?>"></i><?php endforeach; ?></div><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
    <div class="acciones" style="margin-top:14px">
      <?php foreach (TIPOS_EVENTO as $k => $t): ?><span class="tipo-evento t-<?= $k ?>"><?= e($t) ?></span><?php endforeach; ?>
    </div>

    <h3 style="margin-top:22px">Eventos del mes</h3>
    <?php if (!$eventos): ?><div class="vacio"><?= icono('calendario') ?>No hay eventos este mes.</div><?php endif; ?>
    <ul class="lista">
      <?php foreach ($eventos as $ev): echo fila_evento($ev, $admin);
        if ($admin): ?>
          <div class="acciones">
            <a class="btn-icono" href="?m=<?= e($m) ?>&editar=<?= $ev['id'] ?>#form" aria-label="Editar"><?= icono('editar') ?></a>
            <form method="post" data-confirmar="¿Eliminar el evento? Se avisará a los estudiantes que fue cancelado.">
              <?= csrf_campo() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $ev['id'] ?>">
              <button class="btn-icono" type="submit" aria-label="Eliminar"><?= icono('basura') ?></button></form>
          </div>
        <?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($admin):
    $sedes = q('SELECT * FROM sedes ORDER BY nombre')->fetchAll();
    $turnos = q('SELECT * FROM turnos ORDER BY id')->fetchAll();
    $semestres = q('SELECT * FROM semestres ORDER BY numero')->fetchAll();
    $v = $editar ?: ['id' => 0, 'titulo' => '', 'tipo' => 'parcial', 'descripcion' => '', 'fecha_inicio' => '', 'fecha_fin' => '',
                     'sede_id' => null, 'turno_id' => null, 'semestre_id' => null];
    $dt = fn($f) => $f ? date('Y-m-d\TH:i', strtotime($f)) : ''; ?>
  <section class="tarjeta" id="form">
    <h3><?= $editar ? 'Modificar evento' : 'Nuevo evento' ?></h3>
    <?php if ($editar): ?><p class="muy-tenue">Al guardar se avisará del cambio a los estudiantes alcanzados (plataforma + correo).</p><?php endif; ?>
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
      <div class="campo"><label for="titulo">Título</label><input id="titulo" name="titulo" type="text" required maxlength="150" value="<?= e($v['titulo']) ?>"></div>
      <div class="campo"><label for="tipo">Tipo</label><select id="tipo" name="tipo">
        <?php foreach (TIPOS_EVENTO as $k => $t): ?><option value="<?= $k ?>" <?= $v['tipo'] === $k ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
      <div class="form-fila">
        <div class="campo"><label for="fi">Inicio</label><input id="fi" name="fecha_inicio" type="datetime-local" required value="<?= $dt($v['fecha_inicio']) ?>"></div>
        <div class="campo"><label for="ff">Fin <small>(opcional)</small></label><input id="ff" name="fecha_fin" type="datetime-local" value="<?= $dt($v['fecha_fin']) ?>"></div>
      </div>
      <div class="campo"><label for="desc">Descripción</label><textarea id="desc" name="descripcion" rows="3"><?= e($v['descripcion']) ?></textarea></div>
      <p class="etiqueta">¿A quiénes aplica?</p>
      <div class="form-fila form-fila-3">
        <div class="campo"><label for="sem">Semestre</label><select id="sem" name="semestre_id"><option value="">Todos</option>
          <?php foreach ($semestres as $s): ?><option value="<?= $s['id'] ?>" <?= $v['semestre_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="tur">Turno</label><select id="tur" name="turno_id"><option value="">Todos</option>
          <?php foreach ($turnos as $s): ?><option value="<?= $s['id'] ?>" <?= $v['turno_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="sed">Sede</label><select id="sed" name="sede_id"><option value="">Todas</option>
          <?php foreach ($sedes as $s): ?><option value="<?= $s['id'] ?>" <?= $v['sede_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
      </div>
      <?php if (!$editar): ?>
        <label class="casilla"><input type="checkbox" name="notificar" value="1" checked> Notificar a los estudiantes (plataforma + correo)</label>
      <?php endif; ?>
      <div class="acciones" style="margin-top:8px">
        <button class="btn" type="submit"><?= icono('check') ?><?= $editar ? 'Guardar cambios' : 'Crear evento' ?></button>
        <?php if ($editar): ?><a class="btn btn-suave" href="calendario.php?m=<?= e($m) ?>">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php layout_fin('calendario');
