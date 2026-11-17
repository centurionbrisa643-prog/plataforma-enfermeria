<?php
/**
 * Configuración general.
 * Cambiá DB_PASS por la contraseña que pusiste al instalar PostgreSQL.
 */

// ---- Base de datos (la misma que ves en PgAdmin 4) ----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'enfermeria_db');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'TU_CONTRASEÑA_DE_POSTGRES');

// ---- Aplicación ----
define('APP_NOMBRE', 'Libreta de Prácticas · Enfermería UniNorte');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000'); // se usa en los enlaces de los correos
date_default_timezone_set('America/Asuncion');

// ---- Correo (notificación doble) ----
// Mientras SMTP_HABILITADO sea false, las notificaciones se ven en la
// plataforma y los correos quedan "pendientes" para enviarse después.
define('SMTP_HABILITADO', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PUERTO', 587);              // 587 = STARTTLS · 465 = SSL
define('SMTP_SEGURIDAD', 'tls');         // 'tls', 'ssl' o '' (sin cifrado, solo pruebas locales)
define('SMTP_USUARIO', 'tu.cuenta@gmail.com');
define('SMTP_CLAVE', 'contraseña-de-aplicacion');   // en Gmail: "Contraseñas de aplicaciones"
define('SMTP_REMITENTE', 'tu.cuenta@gmail.com');
define('SMTP_NOMBRE_REMITENTE', 'Carrera de Enfermería - UniNorte');
define('SMTP_VERIFICAR_CERT', true);
define('CORREOS_POR_LOTE', 30);          // cuántos se envían al momento; el resto queda pendiente

// ---- Archivos ----
define('DIR_DOCUMENTOS', __DIR__ . '/../storage/documentos');
define('TAMANO_MAX_PDF', 15 * 1024 * 1024); // 15 MB
