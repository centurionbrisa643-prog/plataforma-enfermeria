<?php
/**
 * Documentos de consulta permanente (F01): reglamentos, manuales PAE,
 * guías de procedimientos y protocolos de bioseguridad.
 */
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();
$admin = $u['rol'] === 'admin';

if ($admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'guardar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $cat = $_POST['categoria'] ?? '';
        if ($titulo === '' || !isset(CATEGORIAS_DOC[$cat])) {
            flash('error', 'Completá el título y la categoría.');
            redirigir('documentos.php');
        }
        $archivo = null; $original = null;
        if (!empty($_FILES['archivo']['name'])) {
            $f = $_FILES['archivo'];
            // Un PDF real empieza con "%PDF-"
            $esPdf = $f['error'] === UPLOAD_ERR_OK && file_get_contents($f['tmp_name'], false, null, 0, 5) === '%PDF-';
            if (!$esPdf || $f['size'] > TAMANO_MAX_PDF) {
                flash('error', 'El archivo debe ser un PDF de hasta ' . (TAMANO_MAX_PDF / 1048576) . ' MB.');
                redirigir('documentos.php');
            }
            if (!is_dir(DIR_DOCUMENTOS)) mkdir(DIR_DOCUMENTOS, 0775, true);
            $archivo = bin2hex(random_bytes(16)) . '.pdf';
            move_uploaded_file($f['tmp_name'], DIR_DOCUMENTOS . '/' . $archivo);
            $original = basename($f['name']);
        }
        $datos = [$titulo, $cat, trim($_POST['area'] ?? '') ?: null, trim($_POST['descripcion'] ?? '') ?: null,
                  trim($_POST['version'] ?? '') ?: '1.0', !empty($_POST['publicado']) ? 'true' : 'false'];
        if ($id) {
            $previo = q('SELECT archivo FROM documentos WHERE id = ?', [$id])->fetchColumn();
            q('UPDATE documentos SET titulo=?, categoria=?, area=?, descripcion=?, version=?, publicado=?,
                      archivo = COALESCE(?, archivo), nombre_original = COALESCE(?, nombre_original),
                      subido_por=?, fecha=NOW() WHERE id=?', [...$datos, $archivo, $original, $u['id'], $id]);
            if ($archivo && $previo) @unlink(DIR_DOCUMENTOS . '/' . basename($previo));
        } else {
            q('INSERT INTO documentos (titulo, categoria, area, descripcion, version, publicado, archivo, nombre_original, subido_por)
               VALUES (?,?,?,?,?,?,?,?,?)', [...$datos, $archivo, $original, $u['id']]);
        }
        if ($archivo && !empty($_POST['notificar'])) {
            $ids = q('SELECT id FROM usuarios WHERE rol = \'estudiante\' AND activo')->fetchAll(PDO::FETCH_COLUMN);
            $r = notificar($ids, 'Documento actualizado', '«' . $titulo . '» (versión ' . $datos[4] . ') ya está disponible para consulta.',
                           'documentos.php?cat=' . $cat);
            flash('exito', 'Documento guardado. ' . resumen_notificacion($r));
        } else {
            flash('exito', 'Documento guardado.');
        }
        redirigir('documentos.php?cat=' . $cat);
    }
    if ($accion === 'eliminar' && $id) {
        $arch = q('DELETE FROM documentos WHERE id = ? RETURNING archivo', [$id])->fetchColumn();
        if ($arch) @unlink(DIR_DOCUMENTOS . '/' . basename($arch));
        flash('exito', 'Documento eliminado.');
        redirigir('documentos.php');
    }
}

$cat = isset(CATEGORIAS_DOC[$_GET['cat'] ?? '']) ? $_GET['cat'] : null;
$buscar = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM documentos WHERE 1=1';
$p = [];
if (!$admin) $sql .= ' AND publicado';
if ($cat) { $sql .= ' AND categoria = ?'; $p[] = $cat; }
if ($buscar !== '') { $sql .= ' AND (titulo ILIKE ? OR descripcion ILIKE ? OR area ILIKE ?)'; array_push($p, "%$buscar%", "%$buscar%", "%$buscar%"); }
$docs = q($sql . ' ORDER BY categoria, titulo', $p)->fetchAll();
$conteo = q('SELECT categoria, COUNT(*) FROM documentos' . ($admin ? '' : ' WHERE publicado') . ' GROUP BY categoria')->fetchAll(PDO::FETCH_KEY_PAIR);
$editar = $admin && isset($_GET['editar']) ? q('SELECT * FROM documentos WHERE id = ?', [(int)$_GET['editar']])->fetch() : null;

layout_inicio('Documentos', 'documentos'); ?>

<form class="chip-filtros" method="get">
  <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
  <div class="campo"><label for="q" class="etiqueta">Buscar</label>
    <div class="input-icono"><?= icono('buscar') ?><input id="q" type="search" name="q" value="<?= e($buscar) ?>" placeholder="Título, área o tema"></div></div>
  <button class="btn btn-suave" type="submit">Buscar</button>
</form>

<div class="mosaicos">
  <?php foreach (CATEGORIAS_DOC as $k => [$nombre, $ico]): ?>
    <a class="mosaico" href="?cat=<?= $k ?>" style="<?= $cat === $k ? 'outline:2px solid var(--musgo);background:var(--superficie)' : '' ?>">
      <?= icono($ico) ?><?= e($nombre) ?><small><?= (int)($conteo[$k] ?? 0) ?> documento(s)</small></a>
  <?php endforeach; ?>
</div>

<div class="grilla <?= $admin ? 'grilla-principal' : '' ?>" style="margin-top:22px">
  <section class="tarjeta">
    <div class="tarjeta-titulo"><h3><?= $cat ? e(CATEGORIAS_DOC[$cat][0]) : 'Todos los documentos' ?></h3>
      <?php if ($cat || $buscar): ?><a class="muy-tenue" href="documentos.php">Ver todos</a><?php endif; ?></div>
    <?php if (!$docs): ?><div class="vacio"><?= icono('documento') ?>No hay documentos para mostrar.</div><?php endif; ?>
    <ul class="lista">
      <?php foreach ($docs as $d): ?>
        <li>
          <div class="icono-circulo"><?= icono(CATEGORIAS_DOC[$d['categoria']][1]) ?></div>
          <div class="lista-cuerpo">
            <strong><?= e($d['titulo']) ?></strong>
            <small><?= e(CATEGORIAS_DOC[$d['categoria']][0]) ?><?= $d['area'] ? ' · ' . e($d['area']) : '' ?> · v<?= e($d['version']) ?> · <?= fecha_corta($d['fecha']) ?></small>
            <?php if ($d['descripcion']): ?><div class="muy-tenue"><?= e($d['descripcion']) ?></div><?php endif; ?>
            <?php if ($admin && !$d['publicado']): ?><span class="etiqueta-estado e-alerta">Oculto</span><?php endif; ?>
          </div>
          <div class="acciones">
            <?php if ($d['archivo']): ?>
              <a class="btn btn-sec btn-chico" href="descargar.php?id=<?= $d['id'] ?>" target="_blank" rel="noopener"><?= icono('ojo') ?>Abrir</a>
            <?php else: ?>
              <span class="etiqueta-estado e-neutro">Pendiente de carga</span>
            <?php endif; ?>
            <?php if ($admin): ?>
              <a class="btn-icono" href="?editar=<?= $d['id'] ?>#form" aria-label="Editar"><?= icono('editar') ?></a>
              <form method="post" data-confirmar="¿Eliminar este documento?"><?= csrf_campo() ?>
                <input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button class="btn-icono" type="submit" aria-label="Eliminar"><?= icono('basura') ?></button></form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($admin):
    $v = $editar ?: ['id' => 0, 'titulo' => '', 'categoria' => $cat ?? 'reglamento_practicas', 'area' => '', 'descripcion' => '', 'version' => '1.0', 'publicado' => true]; ?>
  <section class="tarjeta" id="form">
    <h3><?= $editar ? 'Editar documento' : 'Subir documento' ?></h3>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_campo() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
      <div class="campo"><label for="dt">Título</label><input id="dt" name="titulo" type="text" required value="<?= e($v['titulo']) ?>"></div>
      <div class="campo"><label for="dc">Categoría</label><select id="dc" name="categoria">
        <?php foreach (CATEGORIAS_DOC as $k => [$n]): ?><option value="<?= $k ?>" <?= $v['categoria'] === $k ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></div>
      <div class="form-fila">
        <div class="campo"><label for="da">Área <small>(opcional)</small></label><input id="da" name="area" type="text" value="<?= e($v['area']) ?>" placeholder="Ej.: Clínica quirúrgica"></div>
        <div class="campo"><label for="dv">Versión</label><input id="dv" name="version" type="text" value="<?= e($v['version']) ?>"></div>
      </div>
      <div class="campo"><label for="dd">Descripción</label><textarea id="dd" name="descripcion" rows="3"><?= e($v['descripcion']) ?></textarea></div>
      <div class="campo"><label for="da2">Archivo PDF <?= $editar && $editar['archivo'] ? '<small>(dejalo vacío para mantener el actual)</small>' : '' ?></label>
        <input id="da2" name="archivo" type="file" accept="application/pdf"></div>
      <label class="casilla"><input type="checkbox" name="publicado" value="1" <?= $v['publicado'] ? 'checked' : '' ?>> Visible para estudiantes</label>
      <label class="casilla"><input type="checkbox" name="notificar" value="1"> Avisar a los estudiantes si se sube un archivo</label>
      <div class="acciones" style="margin-top:8px"><button class="btn" type="submit"><?= icono('check') ?>Guardar</button>
        <?php if ($editar): ?><a class="btn btn-suave" href="documentos.php">Cancelar</a><?php endif; ?></div>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php layout_fin('documentos');
