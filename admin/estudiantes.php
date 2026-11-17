<?php
/** Administración de estudiantes (solo Dirección y Coordinación de Prácticas). */
define('EN_ADMIN', true);
require __DIR__ . '/../includes/bootstrap.php';
$u = requerir_login('admin');

$sedes = q('SELECT * FROM sedes ORDER BY nombre')->fetchAll();
$turnos = q('SELECT * FROM turnos ORDER BY id')->fetchAll();
$semestres = q('SELECT * FROM semestres ORDER BY numero')->fetchAll();

if (($_POST['accion'] ?? '') === 'crear') {
    $d = array_map('trim', $_POST + ['nombre' => '', 'apellido' => '', 'email' => '', 'clave' => '',
                                     'sede_id' => '0', 'turno_id' => '0', 'semestre_id' => '0']);
    $clave = $d['clave'] !== '' ? $d['clave'] : null;
    $errores = [];
    if ($d['nombre'] === '' || $d['apellido'] === '') $errores[] = 'nombre y apellido';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'un correo válido';
    if (!$clave || strlen($clave) < 8) $errores[] = 'una contraseña temporal de 8 caracteres o más';
    if (q('SELECT 1 FROM usuarios WHERE lower(email) = lower(?)', [$d['email']])->fetchColumn()) $errores[] = 'un correo que no esté registrado';
    if ($errores) {
        flash('error', 'Falta: ' . implode(', ', $errores) . '.');
        redirigir('admin/estudiantes.php?nuevo=1');
    }
    $semNum = (int)q('SELECT numero FROM semestres WHERE id = ?', [(int)$d['semestre_id']])->fetchColumn() ?: 1;

    db()->beginTransaction();
    $id = q("INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, sede_id, turno_id)
             VALUES (?, ?, ?, ?, 'estudiante', ?, ?) RETURNING id",
            [$d['nombre'], $d['apellido'], strtolower($d['email']), password_hash($clave, PASSWORD_DEFAULT),
             (int)$d['sede_id'], (int)$d['turno_id']])->fetchColumn();
    // Semestres anteriores aprobados + actual en curso
    q("INSERT INTO avance_estudiante (estudiante_id, semestre_id, estado, aprobado_por, fecha_aprobacion)
       SELECT ?, id, CASE WHEN numero < ? THEN 'aprobado' ELSE 'cursando' END,
              CASE WHEN numero < ? THEN ?::int END, CASE WHEN numero < ? THEN NOW() END
         FROM semestres WHERE numero <= ?", [$id, $semNum, $semNum, $u['id'], $semNum, $semNum]);
    q('INSERT INTO prerrequisitos_estudiante (estudiante_id, bioseguridad_aprobado, deners_habilitado, actualizado_por, actualizado_en)
       VALUES (?, ?, ?, ?, NOW())', [$id, !empty($_POST['bioseguridad']) ? 'true' : 'false', !empty($_POST['deners']) ? 'true' : 'false', $u['id']]);
    db()->commit();

    notificar([$id], 'Bienvenida a la plataforma', 'Tu cuenta fue creada. Ya podés consultar tu Libreta Digital de Prácticas, el calendario y los documentos de la carrera.', 'inicio.php');
    flash('exito', "Estudiante creado. Entregale su usuario ({$d['email']}) y la contraseña temporal.");
    redirigir('admin/estudiante.php?id=' . $id);
}

// ---- Listado con filtros ----
$f = ['q' => trim($_GET['q'] ?? ''), 'sede' => (int)($_GET['sede'] ?? 0), 'turno' => (int)($_GET['turno'] ?? 0),
      'sem' => (int)($_GET['sem'] ?? 0), 'estado' => $_GET['estado'] ?? ''];
$sql = 'SELECT * FROM v_estudiantes WHERE 1=1';
$p = [];
if ($f['q'] !== '') { $sql .= " AND (nombre || ' ' || apellido ILIKE ? OR email ILIKE ?)"; array_push($p, "%{$f['q']}%", "%{$f['q']}%"); }
if ($f['sede'])  { $sql .= ' AND sede_id = ?';  $p[] = $f['sede']; }
if ($f['turno']) { $sql .= ' AND turno_id = ?'; $p[] = $f['turno']; }
if ($f['sem'])   { $sql .= ' AND semestre_actual = ?'; $p[] = $f['sem']; }
if ($f['estado'] === 'habilitado')    $sql .= ' AND habilitado_practicas';
if ($f['estado'] === 'no_habilitado') $sql .= ' AND NOT habilitado_practicas';
if ($f['estado'] === 'inactivo')      $sql .= ' AND NOT activo';
$lista = q($sql . ' ORDER BY apellido, nombre', $p)->fetchAll();

layout_inicio('Estudiantes', 'estudiantes'); ?>

<details class="plegable" <?= isset($_GET['nuevo']) ? 'open' : '' ?> style="margin-bottom:18px">
  <summary><?= icono('mas') ?>Nuevo estudiante</summary>
  <div class="plegable-cuerpo">
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="crear">
      <div class="form-fila">
        <div class="campo"><label for="n">Nombre</label><input id="n" name="nombre" type="text" required></div>
        <div class="campo"><label for="a">Apellido</label><input id="a" name="apellido" type="text" required></div>
        <div class="campo"><label for="m">Correo (será su usuario)</label><input id="m" name="email" type="email" required></div>
        <div class="campo"><label for="c">Contraseña temporal</label><input id="c" name="clave" type="text" minlength="8" required
             value="<?= e('Enf-' . bin2hex(random_bytes(3))) ?>"><span class="ayuda">Podrá cambiarla desde su perfil.</span></div>
      </div>
      <div class="form-fila form-fila-3">
        <div class="campo"><label for="s">Sede</label><select id="s" name="sede_id"><?php foreach ($sedes as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="t">Turno</label><select id="t" name="turno_id"><?php foreach ($turnos as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="se">Semestre actual</label><select id="se" name="semestre_id"><?php foreach ($semestres as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?></select>
          <span class="ayuda">Los anteriores quedan como aprobados.</span></div>
      </div>
      <label class="casilla"><input type="checkbox" name="bioseguridad" value="1"> Curso de bioseguridad aprobado</label>
      <label class="casilla"><input type="checkbox" name="deners" value="1"> Habilitación DENERS vigente</label>
      <button class="btn" type="submit" style="margin-top:8px"><?= icono('check') ?>Crear estudiante</button>
    </form>
  </div>
</details>

<form class="chip-filtros" method="get">
  <div class="campo"><label class="etiqueta" for="fq">Buscar</label>
    <div class="input-icono"><?= icono('buscar') ?><input id="fq" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nombre o correo"></div></div>
  <div class="campo"><label class="etiqueta" for="fs">Semestre</label><select id="fs" name="sem"><option value="">Todos</option>
    <?php foreach ($semestres as $s): ?><option value="<?= $s['numero'] ?>" <?= $f['sem'] == $s['numero'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
  <div class="campo"><label class="etiqueta" for="ft">Turno</label><select id="ft" name="turno"><option value="">Todos</option>
    <?php foreach ($turnos as $s): ?><option value="<?= $s['id'] ?>" <?= $f['turno'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
  <?php if (count($sedes) > 1): ?>
  <div class="campo"><label class="etiqueta" for="fse">Sede</label><select id="fse" name="sede"><option value="">Todas</option>
    <?php foreach ($sedes as $s): ?><option value="<?= $s['id'] ?>" <?= $f['sede'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
  <?php endif; ?>
  <div class="campo"><label class="etiqueta" for="fe">Estado</label><select id="fe" name="estado">
    <option value="">Todos</option>
    <option value="habilitado" <?= $f['estado'] === 'habilitado' ? 'selected' : '' ?>>Habilitados para prácticas</option>
    <option value="no_habilitado" <?= $f['estado'] === 'no_habilitado' ? 'selected' : '' ?>>Sin habilitación</option>
    <option value="inactivo" <?= $f['estado'] === 'inactivo' ? 'selected' : '' ?>>Cuentas desactivadas</option></select></div>
  <button class="btn btn-suave" type="submit"><?= icono('filtro') ?>Filtrar</button>
</form>

<p class="muy-tenue"><?= count($lista) ?> estudiante(s)</p>
<div class="tabla-envoltura">
  <table class="tabla-tarjetas">
    <thead><tr><th>Estudiante</th><th>Semestre</th><th>Turno</th><th>Bioseguridad</th><th>DENERS</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $e): ?>
      <tr>
        <td class="td-principal"><strong><?= e($e['apellido'] . ', ' . $e['nombre']) ?></strong>
          <?= $e['activo'] ? '' : '<span class="etiqueta-estado e-peligro">Desactivado</span>' ?><br><small><?= e($e['email']) ?></small></td>
        <td data-et="Semestre"><?= (int)$e['semestre_actual'] ?>.º</td>
        <td data-et="Turno"><?= e($e['turno']) ?></td>
        <td data-et="Bioseguridad"><?= $e['bioseguridad_aprobado'] ? '<span class="etiqueta-estado e-exito">' . icono('check') . 'Sí</span>' : '<span class="etiqueta-estado e-alerta">No</span>' ?></td>
        <td data-et="DENERS"><?= $e['deners_habilitado'] ? '<span class="etiqueta-estado e-exito">' . icono('check') . 'Sí</span>' : '<span class="etiqueta-estado e-alerta">No</span>' ?></td>
        <td><a class="btn btn-sec btn-chico" href="<?= url('admin/estudiante.php?id=' . $e['id']) ?>">Gestionar <?= icono('flecha_der') ?></a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="6"><div class="vacio">No hay estudiantes con esos filtros.</div></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php layout_fin('estudiantes');
