<?php
/** Contenido de la Libreta: procedimientos por semestre y área. */
define('EN_ADMIN', true);
require __DIR__ . '/../includes/bootstrap.php';
$u = requerir_login('admin');

$semestres = q('SELECT * FROM semestres ORDER BY numero')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($accion === 'guardar') {
        $d = [(int)$_POST['semestre_id'], trim($_POST['area'] ?? ''), trim($_POST['titulo'] ?? ''),
              trim($_POST['descripcion'] ?? '') ?: null, trim(str_replace("\r", '', $_POST['pasos'] ?? '')) ?: null,
              !empty($_POST['activo']) ? 'true' : 'false'];
        if ($d[1] === '' || $d[2] === '') {
            flash('error', 'Completá el área y el título.');
        } elseif ($id) {
            q('UPDATE procedimientos SET semestre_id=?, area=?, titulo=?, descripcion=?, pasos=?, activo=? WHERE id=?', [...$d, $id]);
            flash('exito', 'Procedimiento actualizado.');
        } else {
            q('INSERT INTO procedimientos (semestre_id, area, titulo, descripcion, pasos, activo) VALUES (?,?,?,?,?,?)', $d);
            flash('exito', 'Procedimiento agregado a la libreta.');
        }
        redirigir('admin/procedimientos.php?sem=' . $d[0]);
    }
}

$semId = (int)($_GET['sem'] ?? $semestres[0]['id']);
$lista = q('SELECT p.*, (SELECT COUNT(*) FROM practicas_realizadas r WHERE r.procedimiento_id = p.id) AS registros
              FROM procedimientos p WHERE semestre_id = ? ORDER BY area, titulo', [$semId])->fetchAll();
$conteo = q('SELECT semestre_id, COUNT(*) FROM procedimientos WHERE activo GROUP BY semestre_id')->fetchAll(PDO::FETCH_KEY_PAIR);
$editar = isset($_GET['editar']) ? q('SELECT * FROM procedimientos WHERE id = ?', [(int)$_GET['editar']])->fetch() : null;
$v = $editar ?: ['id' => 0, 'semestre_id' => $semId, 'area' => '', 'titulo' => '', 'descripcion' => '', 'pasos' => '', 'activo' => true];

layout_inicio('Libreta · Procedimientos', 'procedimientos'); ?>

<p class="tenue">La libreta es de consulta: aquí se cargan los procedimientos que cada estudiante verá según su semestre habilitado.</p>
<nav class="pestanas" aria-label="Semestres">
  <?php foreach ($semestres as $s): ?>
    <a class="pestana <?= $s['id'] == $semId ? 'activa' : '' ?>" href="?sem=<?= $s['id'] ?>"><?= e($s['nombre']) ?> <small>(<?= (int)($conteo[$s['id']] ?? 0) ?>)</small></a>
  <?php endforeach; ?>
</nav>

<div class="grilla grilla-principal">
  <section>
    <?php if (!$lista): ?><div class="tarjeta vacio"><?= icono('libreta') ?>Este semestre todavía no tiene procedimientos.</div><?php endif; ?>
    <?php foreach ($lista as $p): ?>
      <div class="procedimiento" style="<?= $p['activo'] ? '' : 'opacity:.6' ?>">
        <div class="icono-circulo"><?= icono('pulso') ?></div>
        <a class="lista-cuerpo" href="<?= url('procedimiento.php?id=' . $p['id']) ?>" style="color:inherit;text-decoration:none">
          <span class="area"><?= e($p['area']) ?></span><strong><?= e($p['titulo']) ?></strong>
          <small><?= count(array_filter(explode("\n", (string)$p['pasos']))) ?> pasos · <?= (int)$p['registros'] ?> práctica(s) registrada(s)
            <?= $p['activo'] ? '' : ' · <b>oculto</b>' ?></small></a>
        <a class="btn-icono" href="?sem=<?= $semId ?>&editar=<?= $p['id'] ?>#form" aria-label="Editar"><?= icono('editar') ?></a>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="tarjeta" id="form">
    <h3><?= $editar ? 'Editar procedimiento' : 'Nuevo procedimiento' ?></h3>
    <form method="post">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
      <div class="form-fila">
        <div class="campo"><label for="ps">Semestre</label><select id="ps" name="semestre_id">
          <?php foreach ($semestres as $s): ?><option value="<?= $s['id'] ?>" <?= $v['semestre_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="campo"><label for="pa">Área de atención</label><input id="pa" name="area" type="text" required value="<?= e($v['area']) ?>" placeholder="Ej.: Fundamentos"></div>
      </div>
      <div class="campo"><label for="pt">Título</label><input id="pt" name="titulo" type="text" required value="<?= e($v['titulo']) ?>"></div>
      <div class="campo"><label for="pd">Descripción</label><textarea id="pd" name="descripcion" rows="2"><?= e($v['descripcion']) ?></textarea></div>
      <div class="campo"><label for="pp">Pasos</label><textarea id="pp" name="pasos" rows="7" placeholder="Un paso por línea"><?= e($v['pasos']) ?></textarea>
        <span class="ayuda">Escribí un paso por línea; se numeran solos.</span></div>
      <label class="casilla"><input type="checkbox" name="activo" value="1" <?= $v['activo'] ? 'checked' : '' ?>> Visible en la libreta</label>
      <div class="acciones" style="margin-top:8px"><button class="btn" type="submit"><?= icono('check') ?>Guardar</button>
        <?php if ($editar): ?><a class="btn btn-suave" href="?sem=<?= $semId ?>">Cancelar</a><?php endif; ?></div>
    </form>
  </section>
</div>
<?php layout_fin('procedimientos');
