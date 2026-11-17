# Plataforma Académica · Carrera de Enfermería (UniNorte)

Libreta Digital de Prácticas, calendario, documentos y notificaciones dobles.
**Tecnologías:** PostgreSQL + PgAdmin 4 · PHP 8.1 o superior · HTML/CSS/JS · Visual Studio Code (Windows)

---

## 1. Instalar lo necesario (una sola vez)

| Programa | Dónde | Nota |
|---|---|---|
| **PostgreSQL** (trae PgAdmin 4) | postgresql.org/download/windows | Anotá la contraseña del usuario `postgres` |
| **PHP 8.2 o superior** | windows.php.net/download → *VS17 x64 Thread Safe* (zip) | Descomprimir en `C:\php` |
| **Visual Studio Code** | code.visualstudio.com | Extensión recomendada: *PHP Intelephense* |

### Configurar PHP
1. En `C:\php`, copiá `php.ini-development` y renombrala a `php.ini`.
2. Abrí `php.ini` y quitá el `;` del inicio de estas líneas:
   ```ini
   extension_dir = "ext"
   extension=pdo_pgsql
   extension=pgsql
   extension=openssl
   extension=mbstring
   ```
3. Agregá `C:\php` al **PATH** de Windows (Buscar → "variables de entorno" → Path → Nuevo).
4. En una terminal nueva comprobá: `php -v` y `php -m` (debe aparecer `pdo_pgsql`).

> ¿Usás XAMPP? Funciona igual: activá `extension=pdo_pgsql` y `extension=pgsql` en `C:\xampp\php\php.ini`.

## 2. Crear la base de datos en PgAdmin 4
1. Abrí PgAdmin 4 → *Servers* → *PostgreSQL* → clic derecho en **Databases** → *Create* → *Database…*
2. Nombre: `enfermeria_db` → *Save*.
3. Clic derecho en `enfermeria_db` → **Query Tool**.
4. Abrí el archivo `sql/enfermeria_db.sql` (ícono de carpeta) y ejecutalo con **F5**.
5. Verificá: en *Schemas → public → Tables* deben aparecer 11 tablas y la vista `v_estudiantes`.

> El script se puede volver a ejecutar cuando quieras: borra todo y carga los datos de prueba de nuevo.

## 3. Abrir el proyecto en VS Code
1. *Archivo → Abrir carpeta…* → `plataforma-enfermeria`.
2. En `config/config.php` cambiá `DB_PASS` por tu contraseña de PostgreSQL.
3. Abrí la terminal de VS Code (**Ctrl + ñ**) y ejecutá:
   ```bash
   php -S localhost:8000 router.php
   ```
4. Entrá a **http://localhost:8000** en el navegador.
   Para verla desde el celular (misma red Wi-Fi): `php -S 0.0.0.0:8000 router.php` y abrí `http://IP-de-tu-PC:8000`.

## 4. Usuarios de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador (Dirección) | direccion@enfermeria.test | Admin2026! |
| Administrador (Coordinación de Prácticas) | coordinacion@enfermeria.test | Admin2026! |
| Estudiante (3.º sem., habilitada) | ana.benitez@enfermeria.test | Estudiante2026! |
| Estudiante (1.º sem., sin DENERS) | luis.gimenez@enfermeria.test | Estudiante2026! |

Los procedimientos y documentos cargados son **ejemplos**. El contenido oficial se carga
después de la visita técnica (pendiente del Acta N.º 01).

## 5. Activar el envío de correos (notificación doble)
Mientras no se configure, los avisos se ven en la plataforma y los correos quedan **pendientes**.

1. Con una cuenta de Gmail: activá la verificación en 2 pasos y creá una
   **Contraseña de aplicación** (Cuenta de Google → Seguridad → Contraseñas de aplicaciones).
2. En `config/config.php`:
   ```php
   define('SMTP_HABILITADO', true);
   define('SMTP_USUARIO', 'tu.cuenta@gmail.com');
   define('SMTP_CLAVE', 'xxxx xxxx xxxx xxxx');   // la contraseña de aplicación
   define('SMTP_REMITENTE', 'tu.cuenta@gmail.com');
   ```
3. PHP en Windows necesita los certificados para conectarse con TLS: descargá `cacert.pem`
   de **curl.se/docs/caextract.html**, guardalo en `C:\php\extras\ssl\cacert.pem` y en `php.ini` poné:
   ```ini
   openssl.cafile = "C:\php\extras\ssl\cacert.pem"
   ```
4. Los pendientes se envían con el botón **"Enviar correos pendientes"** (Inicio del admin) o con
   `php tareas/enviar_correos.php` (se puede programar en el Programador de tareas de Windows).

No se usa Composer: el envío SMTP está en `includes/smtp.php`.

## 6. Estructura

```
plataforma-enfermeria/
├── sql/enfermeria_db.sql      ← script para PgAdmin 4
├── config/config.php          ← conexión a PostgreSQL y correo
├── includes/                  ← conexión, sesión, roles, notificaciones, diseño
├── assets/ (css, js, img)     ← estilos verde olivo/militar/musgo, modo oscuro, logo
├── admin/                     ← estudiantes, ficha del estudiante, procedimientos
├── login.php  inicio.php  libreta.php  procedimiento.php
├── calendario.php  documentos.php  notificaciones.php  perfil.php
├── storage/documentos/        ← PDFs subidos (no accesibles directamente)
├── tareas/enviar_correos.php  ← reintento de correos
└── router.php                 ← para el servidor de desarrollo de PHP
```

## 7. Trazabilidad con las fuentes del proyecto

| Funcionalidad | Origen |
|---|---|
| Libreta Digital de Prácticas | F01 (correo) |
| Libreta **solo de consulta**; el estudiante no carga datos; firma física del instructor | F02 (Acta N.º 01) |
| Correcciones de errores en forma presencial (no hay módulo de correcciones) | F02 |
| Roles: Dirección y Coordinación de Prácticas = Administrador | F02 |
| Semestres, turnos (mañana/tarde/noche) y avance progresivo | F02 |
| El semestre siguiente se ve solo si aprobó el actual | F02 |
| Prerrequisitos: curso de bioseguridad + habilitación DENERS | F02 |
| Calendario con parciales, finales y avisos de cambios | F01 + F02 |
| Usuario y contraseña; ~150 estudiantes por sede | F02 |
| Reglamentos, manuales PAE, guías y protocolos de bioseguridad | F01 |
| Notificación **doble** (plataforma + correo) | Decisión del grupo |
| El **admin** marca la aprobación del semestre | Decisión del grupo |
| El **admin** registra en el sistema la práctica firmada en papel | Decisión del grupo |
| Sistema HIS | Fuera de alcance (solo referencia, F02) |
| Lista de Cotejo y Horas de Extensión | F01, no tratados en F02 → versión 2 |

Las decisiones del grupo deben registrarse como **CAM-xx** y validarse con la Dirección en la visita técnica.

## 8. Seguridad incluida
Contraseñas con `password_hash` (bcrypt) · consultas preparadas (PDO) · protección CSRF en formularios ·
bloqueo tras 5 intentos fallidos · control de rol en cada página · PDFs servidos solo con sesión iniciada.
