<?php
/**
 * Ficha del estudiante para el administrador:
 *  - Prerrequisitos: bioseguridad + DENERS (F02)
 *  - Aprobación del semestre → habilita el siguiente (F02 + DG: lo marca el admin)
 *  - Registro de prácticas firmadas en papel (DG)
 */
define('EN_ADMIN', true);
require __DIR__ . '/../includes/bootstrap.php';
$u = requerir_login('admin');

$id = (int)($_GET['id'] ?? $_POST['estudiante_id'] ?? 0);
$est = q("SELECT * FROM usuarios WHERE id = ? AND rol = 'estudiante'", [$id])->fetch();
if (!$est) { flash('error', 'Estudiante no encontrado.'); redirigir('admin/estudiantes.php'); }
$volver = 'admin/estudiante.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'requisitos') {
        $bio = !empty($_POST['bioseguridad']);
        $den = !empty($_POST['deners']);
        q('INSERT INTO prerrequisitos_estudiante (estudiante_id, bioseguridad_aprobado, fecha_bioseguridad, deners_habilitado, fecha_deners, actualizado_por, actualizado_en)
           VALUES (?, ?, ?, ?, ?, ?, NOW())
           ON CONFLICT (estudiante_id) DO UPDATE SET bioseguridad_aprobado = EXCLUDED.bioseguridad_aprobado,
             fecha_bioseguridad = EXCLUDED.fecha_bioseguridad, deners_habilitado = EXCLUDED.deners_habilitado,
             fecha_deners = EXCLUDED.fecha_deners, actualizado_por = EXCLUDED.actualizado_por, actualizado_en = NOW()',
          [$id, $bio ? 'true' : 'false', $bio ? (($_POST['fecha_bio'] ?? '') ?: null) : null,
                $den ? 'true' : 'false', $den ? (($_POST['fecha_deners'] ?? '') ?: null) : null, $u['id']]);
        $estado = $bio && $den ? 'Ya estás habilitado/a para realizar prácticas.'
                : 'Pendiente: ' . implode(' y ', array_filter([$bio ? null : 'curso de bioseguridad', $den ? null : 'habilitación DENERS'])) . '.';
        $r = notificar([$id], 'Actualización de tus requisitos', $estado, 'perfil.php#requisitos');
        flash('exito', 'Requisitos guardados. ' . resumen_notificacion($r));
    }

    if ($accion === 'aprobar_semestre') {
        $sem = q("SELECT a.id, s.numero, s.nombre FROM avance_estudiante a JOIN semestres s ON s.id = a.semestre_id
                   WHERE a.estudiante_id = ? AND a.estado = 'cursando' ORDER BY s.numero DESC LIMIT 1", [$id])->fetch();
        if ($sem) {
            db()->beginTransaction();
            q("UPDATE avance_estudiante SET estado = 'aprobado', aprobado_por = ?, fecha_aprobacion = NOW() WHERE id = ?", [$u['id'], $sem['id']]);
            $sig = q('SELECT id, nombre FROM semestres WHERE numero = ?', [$sem['numero'] + 1])->fetch();
            if ($sig) q('INSERT INTO avance_estudiante (estudiante_id, semestre_id) VALUES (?, ?) ON CONFLICT DO NOTHING', [$id, $sig['id']]);
            db()->commit();
            $msg = "Aprobaste el {$sem['nombre']}." . ($sig ? " Ya podés ver los procedimientos del {$sig['nombre']} en tu libreta." : ' ¡Completaste todos los semestres!');
            $r = notificar([$id], 'Semestre aprobado', $msg, 'libreta.php');
            flash('exito', "{$sem['nombre']} aprobado" . ($sig ? " y {$sig['nombre']} habilitado" : '') . '. ' . resumen_notificacion($r));
        }
    }

    if ($accion === 'deshacer_aprobacion') {
        // Por si se aprobó por error: vuelve el último semestre aprobado a "cursando"
        $ult = q("SELECT a.id, s.numero FROM avance_estudiante a JOIN semestres s ON s.id = a.semestre_id
                   WHERE a.estudiante_id = ? AND a.estado = 'aprobado' ORDER BY s.numero DESC LIMIT 1", [$id])->fetch();
        if ($ult) {
            db()->beginTransaction();
            q("DELETE FROM avance_estudiante a USING semestres s WHERE a.semestre_id = s.id AND a.estudiante_id = ? AND s.numero > ?", [$id, $ult['numero']]);
            q("UPDATE avance_estudiante SET estado = 'cursando', aprobado_por = NULL, fecha_aprobacion = NULL WHERE id = ?", [$ult['id']]);
            db()->commit();
            flash('exito', 'Se deshizo la última aprobación.');
        }
    }

    if ($accion === 'registrar_practica') {
        $pid = (int)($_POST['procedimiento_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $instr = trim($_POST['instructor'] ?? '');
        $hab = (bool)q('SELECT habilitado_practicas FROM v_estudiantes WHERE id = ?', [$id])->fetchColumn();
        $proc = q('SELECT p.titulo FROM procedimientos p JOIN avance_estudiante a ON a.semestre_id = p.semestre_id
                    WHERE p.id = ? AND a.estudiante_id = ?', [$pid, $id])->fetch();
        if (!$hab) {
            flash('error', 'No se puede registrar: el estudiante no está habilitado para prácticas (bioseguridad / DENERS).');
        } elseif (!$proc || !$fecha || $instr === '' || $fecha > date('Y-m-d')) {
            flash('error', 'Indicá la fecha (no futura) y el instructor que firmó la libreta.');
        } else {
            q('INSERT INTO practicas_realizadas (estudiante_id, procedimiento_id, fecha_realizacion, instructor, registrado_por)
               VALUES (?, ?, ?, ?, ?) ON CONFLICT (estudiante_id, procedimiento_id) DO NOTHING', [$id, $pid, $fecha, $instr, $u['id']]);
            $r = notificar([$id], 'Práctica registrada', "Se registró la práctica «{$proc['titulo']}» del " . fecha_corta($fecha) . ", firmada por $instr.",
                           'procedimiento.php?id=' . $pid);
            flash('exito', 'Práctica registrada. ' . resumen_notificacion($r));
        }
    }

    if ($accion === 'anular_practica') {
        q('DELETE FROM practicas_realizadas WHERE id = ? AND estudiante_id = ?', [(int)$_POST['practica_id'], $id]);
        flash('exito', 'Registro anulado.');
    }

    if ($accion === 'datos') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $dup = q('SELECT 1 FROM usuarios WHERE lower(email) = ? AND id <> ?', [$email, $id])->fetchColumn();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $dup || trim($_POST['nombre'] ?? '') === '') {
            flash('error', 'Revisá el nombre y que el correo sea válido y no esté repetido.');
        } else {
            q('UPDATE usuarios SET nombre=?, apellido=?, email=?, sede_id=?, turno_id=?, activo=? WHERE id=?',
              [trim($_POST['nombre']), trim($_POST['apellido'] ?? ''), $email, (int)$_POST['sede_id'], (int)$_POST['turno_id'],
               !empty($_POST['activo']) ? 'true' : 'false', $id]);
            if (($_POST['clave'] ?? '') !== '') {
                if (strlen($_POST['clave']) < 8) flash('error', 'La contraseña debe tener al menos 8 caracteres; no se cambió.');
                else q('UPDATE usuarios SET password_hash = ? WHERE id = ?', [password_hash($_POST['clave'], PASSWORD_DEFAULT), $id]);
            }
            flash('exito', 'Datos actualizados.');
        }
    }
    redirigir($volver . (isset($_POST['ancla']) ? '#' . preg_replace('/\W/', '', $_POST['ancla']) : ''));
}

$v = datos_estudiante($id);
$prog = progreso_libreta($id);
$pre = q('SELECT * FROM prerrequisitos_estudiante WHERE estudiante_id = ?', [$id])->fetch() ?: [];
$cursando = array_values(array_filter($v['semestres'], fn($s) => $s['estado'] === 'cursando'));
$hayAprobados = (bool)array_filter($v['semestres'], fn($s) => $s['estado'] === 'aprobado');
$procs = q('SELECT p.id, p.titulo, p.area, p.semestre_id, s.nombre AS semestre, r.id AS practica_id, r.fecha_realizacion, r.instructor
              FROM procedimientos p
              JOIN semestres s ON s.id = p.semestre_id
              JOIN avance_estudiante a ON a.semestre_id = p.semestre_id AND a.estudiante_id = ?
              LEFT JOIN practicas_realizadas r ON r.procedimiento_id = p.id AND r.estudiante_id = ?
             WHERE p.activo ORDER BY s.numero DESC, p.area, p.titulo', [$id, $id])->fetchAll();
$sedes = q('SELECT * FROM sedes ORDER BY nombre')->fetchAll();
$turnos = q('SELECT * FROM turnos ORDER BY id')->fetchAll();

layout_inicio($est['nombre'] . ' ' . $est['apellido'], 'estudiantes', ['volver' => 'admin/estudiantes.php']); ?>

<a href="<?= url('admin/estudiantes.php') ?>" class="muy-tenue" style="display:inline-flex;gap:4px;align-items:center;margin-bottom:12px"><?= icono('flecha_izq') ?>Estudiantes</a>

<section class="tarjeta" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
  <div class="avatar avatar-grande" style="background:var(--musgo-200)"><?= e(iniciales($est['nombre'], $est['apellido'])) ?></div>
  <div style="flex:1;min-width:200px">
    <h2 style="margin:0"><?= e($est['apellido'] . ', ' . $est['nombre']) ?></h2>
    <small><?= e($est['email']) ?> · <?= e($v['sede']) ?> · <?= e($v['turno']) ?> · <?= (int)$v['semestre_actual'] ?>.º semestre</small>
  </div>
  <?= $v['habilitado_practicas'] ? '<span class="etiqueta-estado e-exito">' . icono('check') . 'Habilitado para prácticas</span>'
                                 : '<span class="etiqueta-estado e-alerta">' . icono('alerta') . 'Sin habilitación</span>' ?>
  <?= $est['activo'] ? '' : '<span class="etiqueta-estado e-peligro">Cuenta desactivada</span>' ?>
</section>

<div class="grilla grilla-2" style="margin-top:18px">
  <section class="tarjeta" id="requisitos">
    <h3>Requisitos para prácticas</h3>
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="requisitos"><input type="hidden" name="ancla" value="requisitos">
      <label class="casilla"><input type="checkbox" name="bioseguridad" value="1" <?= !empty($pre['bioseguridad_aprobado']) ? 'checked' : '' ?>> Curso de bioseguridad aprobado</label>
      <div class="campo"><label for="fb">Fecha de aprobación</label><input id="fb" type="date" name="fecha_bio" value="<?= e($pre['fecha_bioseguridad'] ?? '') ?>"></div>
      <label class="casilla"><input type="checkbox" name="deners" value="1" <?= !empty($pre['deners_habilitado']) ? 'checked' : '' ?>> Habilitación ante la DENERS</label>
      <div class="campo"><label for="fd">Fecha de habilitación</label><input id="fd" type="date" name="fecha_deners" value="<?= e($pre['fecha_deners'] ?? '') ?>"></div>
      <button class="btn" type="submit"><?= icono('check') ?>Guardar y notificar</button>
    </form>
  </section>

  <section class="tarjeta" id="avance">
    <h3>Avance por semestre</h3>
    <ul class="lista">
      <?php foreach ($v['semestres'] as $s):
        $pg = $prog[$s['id']] ?? ['total' => 0, 'hechas' => 0]; ?>
        <li><div class="icono-circulo <?= $s['estado'] === 'aprobado' ? 'hecho' : '' ?>"><?= icono($s['estado'] === 'aprobado' ? 'check' : 'reloj') ?></div>
          <div class="lista-cuerpo"><strong><?= e($s['nombre']) ?></strong>
            <small><?= $s['estado'] === 'aprobado' ? 'Aprobado el ' . fecha_corta($s['fecha_aprobacion']) : 'En curso' ?> · prácticas <?= $pg['hechas'] ?>/<?= $pg['total'] ?></small></div></li>
      <?php endforeach; ?>
    </ul>
    <div class="acciones" style="margin-top:12px">
      <?php if ($cursando): ?>
        <form method="post" data-confirmar="¿Confirmar que aprobó el <?= e($cursando[0]['nombre']) ?>? Se habilitará el siguiente semestre y se le avisará.">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="aprobar_semestre"><input type="hidden" name="ancla" value="avance">
          <button class="btn" type="submit"><?= icono('birrete') ?>Aprobar <?= e($cursando[0]['nombre']) ?></button></form>
      <?php endif; ?>
      <?php if ($hayAprobados): ?>
        <form method="post" data-confirmar="¿Deshacer la última aprobación? Se ocultará el semestre siguiente.">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="deshacer_aprobacion"><input type="hidden" name="ancla" value="avance">
          <button class="btn btn-suave btn-chico" type="submit">Deshacer última aprobación</button></form>
      <?php endif; ?>
    </div>
  </section>
</div>

<section class="tarjeta" id="practicas" style="margin-top:18px">
  <div class="tarjeta-titulo"><h3>Prácticas firmadas en la libreta física</h3></div>
  <p class="muy-tenue">Registrá aquí cada práctica que el instructor firmó en papel. El estudiante la verá marcada en su libreta y recibirá el aviso.</p>
  <?php if (!$v['habilitado_practicas']): ?>
    <div class="aviso aviso-alerta"><?= icono('alerta') ?><span>Para registrar prácticas, el estudiante debe tener aprobados la bioseguridad y la DENERS.</span></div>
  <?php endif; ?>
  <?php $semAct = null; foreach ($procs as $p):
    if ($p['semestre'] !== $semAct): $semAct = $p['semestre']; ?><h3 style="margin:18px 0 6px"><?= e($semAct) ?></h3><?php endif; ?>
    <div class="procedimiento" style="flex-wrap:wrap">
      <div class="icono-circulo <?= $p['fecha_realizacion'] ? 'hecho' : '' ?>"><?= icono($p['fecha_realizacion'] ? 'check' : 'pulso') ?></div>
      <div class="lista-cuerpo" style="min-width:180px"><span class="area"><?= e($p['area']) ?></span><strong><?= e($p['titulo']) ?></strong>
        <?php if ($p['fecha_realizacion']): ?><small><?= fecha_corta($p['fecha_realizacion']) ?> · firmó <?= e($p['instructor']) ?></small><?php endif; ?></div>
      <?php if ($p['fecha_realizacion']): ?>
        <form method="post" data-confirmar="¿Anular este registro? Usalo solo si se cargó por error.">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="anular_practica"><input type="hidden" name="practica_id" value="<?= $p['practica_id'] ?>">
          <input type="hidden" name="ancla" value="practicas">
          <button class="btn btn-peligro btn-chico" type="submit">Anular</button></form>
      <?php elseif ($v['habilitado_practicas']): ?>
        <form method="post" class="acciones" style="flex:1 1 320px;justify-content:flex-end">
          <?= csrf_campo() ?><input type="hidden" name="accion" value="registrar_practica"><input type="hidden" name="procedimiento_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="ancla" value="practicas">
          <input type="date" name="fecha" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required aria-label="Fecha" style="width:auto;flex:0 0 160px;min-height:38px">
          <input type="text" name="instructor" required placeholder="Instructor que firmó" aria-label="Instructor" style="width:auto;flex:1 1 150px;min-height:38px">
          <button class="btn btn-chico" type="submit"><?= icono('check') ?>Registrar</button>
        </form>
      <?php else: ?>
        <span class="etiqueta-estado e-neutro">Pendiente</span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$procs): ?><div class="vacio">No hay procedimientos cargados para sus semestres.</div><?php endif; ?>
</section>

<details class="plegable" style="margin-top:18px">
  <summary><?= icono('editar') ?>Editar datos de la cuenta</summary>
  <div class="plegable-cuerpo">
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="datos">
      <div class="form-fila">
        <div class="campo"><label for="en">Nombre</label><input id="en" name="nombre" type="text" value="<?= e($est['nombre']) ?>" required></div>
        <div class="campo"><label for="ea">Apellido</label><input id="ea" name="apellido" type="text" value="<?= e($est['apellido']) ?>" required></div>
        <div class="campo"><label for="ee">Correo</label><input id="ee" name="email" type="email" value="<?= e($est['email']) ?>" required></div>
        <div class="campo"><label for="ec">Nueva contraseña <small>(opcional)</small></label><input id="ec" name="clave" type="text" minlength="8" placeholder="Dejar vacío para no cambiar"></div>
        <div class="campo"><label for="es">Sede</label><select id="es" name="sede_id"><?php foreach ($sedes as $s): ?><option value="<?= $s['id'] ?>" <?= $est['sede_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="et">Turno</label><select id="et" name="turno_id"><?php foreach ($turnos as $s): ?><option value="<?= $s['id'] ?>" <?= $est['turno_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
      </div>
      <label class="casilla"><input type="checkbox" name="activo" value="1" <?= $est['activo'] ? 'checked' : '' ?>> Cuenta activa (si se desactiva, no puede ingresar)</label>
      <button class="btn" type="submit" style="margin-top:8px"><?= icono('check') ?>Guardar datos</button>
    </form>
  </div>
</details>
<?php layout_fin('estudiantes');
