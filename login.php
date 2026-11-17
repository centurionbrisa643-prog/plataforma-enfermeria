<?php
require __DIR__ . '/includes/bootstrap.php';

if (usuario_actual()) redirigir('inicio.php');

$error = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $email = trim($_POST['email'] ?? '');
    if (intentar_login($email, $_POST['clave'] ?? '')) {
        redirigir('inicio.php');
    }
    $error = ($_SESSION['intentos'] ?? 0) >= 5
        ? 'Demasiados intentos. Esperá un minuto y volvé a probar.'
        : 'Correo o contraseña incorrectos.';
}

cabeza_html('Iniciar sesión'); ?>
<body>
<div class="ingreso">
  <section class="ingreso-arte">
    <?= str_replace('class="btn-icono boton-tema"', 'class="btn-icono boton-tema ingreso-tema"', boton_tema()) ?>
    <div class="latido"><?= icono('pulso') ?></div>
    <div class="ingreso-logo"><img src="assets/img/logo-enfermeria.png" alt="Logo oficial de la Carrera de Enfermería, Universidad del Norte"></div>
    <h1>Libreta de Prácticas</h1>
    <span class="pildora">Enfermería</span>
    <p>Plataforma académica de la Carrera de Enfermería · Universidad del Norte</p>
    <svg class="ondas" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
      <path fill="currentColor" d="M0 64c180 40 360 56 540 40S900 24 1080 24s300 32 360 48v48H0z"/>
    </svg>
  </section>

  <section class="ingreso-form">
    <div class="tarjeta">
      <h2>Iniciar sesión</h2>
      <p class="tenue">Ingresá con el usuario y la contraseña que te entregó la Coordinación de Prácticas.</p>
      <?php if ($error): ?>
        <div class="aviso aviso-error" role="alert"><?= icono('alerta') ?><span><?= e($error) ?></span></div>
      <?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_campo() ?>
        <div class="campo">
          <label for="email">Correo electrónico</label>
          <div class="input-icono"><?= icono('correo') ?>
            <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" placeholder="nombre@uninorte.edu.py"></div>
        </div>
        <div class="campo">
          <label for="clave">Contraseña</label>
          <div class="input-icono"><?= icono('candado') ?>
            <input id="clave" name="clave" type="password" autocomplete="current-password" required placeholder="••••••••"></div>
        </div>
        <button class="btn btn-bloque" type="submit">Iniciar sesión</button>
      </form>
      <p class="muy-tenue" style="margin-top:18px;text-align:center">
        ¿Olvidaste tu contraseña o no tenés cuenta?<br>Solicitala a la Coordinación de Prácticas.</p>
    </div>
  </section>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
