<?php
/** Notificaciones dentro de la plataforma (la otra mitad va por correo). */
require __DIR__ . '/includes/bootstrap.php';
$u = requerir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['accion'] ?? '') === 'leer_todas') {
        q('UPDATE notificaciones SET leida = TRUE WHERE usuario_id = ? AND NOT leida', [$u['id']]);
    } elseif (($_POST['accion'] ?? '') === 'abrir') {
        $n = q('UPDATE notificaciones SET leida = TRUE WHERE id = ? AND usuario_id = ? RETURNING enlace',
               [(int)$_POST['id'], $u['id']])->fetchColumn();
        if ($n && preg_match('/^[a-z\/_]+\.php(\?[\w=&\-]*)?$/', $n)) redirigir($n);
    }
    redirigir('notificaciones.php');
}

$lista = q('SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 100', [$u['id']])->fetchAll();
$sinLeer = count(array_filter($lista, fn($n) => !$n['leida']));

layout_inicio('Notificaciones', 'notificaciones'); ?>
<div style="max-width:760px">
  <div class="tarjeta-titulo">
    <p class="tenue" style="margin:0"><?= $sinLeer ? "Tenés $sinLeer sin leer." : 'Estás al día.' ?> Los avisos también llegan a <strong><?= e($u['email']) ?></strong>.</p>
    <?php if ($sinLeer): ?>
      <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="leer_todas">
        <button class="btn btn-suave btn-chico" type="submit"><?= icono('check') ?>Marcar todas como leídas</button></form>
    <?php endif; ?>
  </div>

  <?php if (!$lista): ?><div class="tarjeta vacio"><?= icono('campana') ?>Todavía no tenés notificaciones.</div><?php endif; ?>
  <?php foreach ($lista as $n): ?>
    <article class="notif <?= $n['leida'] ? '' : 'nueva' ?>">
      <div class="icono-circulo"><?= icono(str_contains($n['titulo'], 'alendario') || str_contains($n['titulo'], 'Evento') ? 'calendario'
          : (str_contains($n['titulo'], 'ráctica') ? 'check_circulo' : (str_contains($n['titulo'], 'emestre') ? 'birrete' : 'campana'))) ?></div>
      <div class="lista-cuerpo">
        <strong><?= e($n['titulo']) ?></strong>
        <p style="margin:2px 0 4px"><?= nl2br(e($n['mensaje'])) ?></p>
        <small><?= hace($n['fecha']) ?></small>
        <div class="canales">
          <span class="etiqueta-estado e-exito"><?= icono('campana') ?>Plataforma</span>
          <?php if ($n['correo_enviado']): ?>
            <span class="etiqueta-estado e-exito"><?= icono('correo') ?>Correo enviado</span>
          <?php else: ?>
            <span class="etiqueta-estado e-neutro"><?= icono('correo') ?>Correo pendiente</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($n['enlace']): ?>
        <form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="abrir"><input type="hidden" name="id" value="<?= $n['id'] ?>">
          <button class="btn-icono" type="submit" aria-label="Abrir"><?= icono('flecha_der') ?></button></form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<?php layout_fin('notificaciones');
