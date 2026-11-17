<?php
/**
 * Se incluye al inicio de cada página.
 */
require_once __DIR__ . '/../config/config.php';

ini_set('session.use_strict_mode', '1');
session_name('enf_sesion');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/iconos.php';
require_once __DIR__ . '/notificar.php';
require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/layout.php';
