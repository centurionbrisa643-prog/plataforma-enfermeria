-- =====================================================================
--  Plataforma Académica - Carrera de Enfermería (UniNorte)
--  Script de base de datos para PostgreSQL (ejecutar en PgAdmin 4)
--
--  Paso 1: crear la base vacía (clic derecho en "Databases" > Create)
--          con el nombre: enfermeria_db
--  Paso 2: abrir el Query Tool SOBRE enfermeria_db, pegar este script
--          completo y ejecutarlo (F5).
--
--  Trazabilidad:
--    F01 = Correo de la Dirección de Enfermería (06/08/2026)
--    F02 = Acta N.º 01, reunión del 01/09/2026
--    DG  = Decisión del grupo (pendiente de confirmar en visita técnica)
-- =====================================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;   -- para encriptar contraseñas de prueba

-- Limpieza (permite volver a ejecutar el script desde cero)
DROP VIEW IF EXISTS v_estudiantes CASCADE;
DROP TABLE IF EXISTS notificaciones, eventos_calendario, documentos,
    practicas_realizadas, procedimientos, prerrequisitos_estudiante,
    avance_estudiante, usuarios, semestres, turnos, sedes CASCADE;

-- ---------------------------------------------------------------------
-- Estructura académica (F02: semestres, turnos, ~150 estudiantes por sede)
-- ---------------------------------------------------------------------
CREATE TABLE sedes (
    id      SERIAL PRIMARY KEY,
    nombre  VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE turnos (
    id      SERIAL PRIMARY KEY,
    nombre  VARCHAR(20) NOT NULL UNIQUE          -- Mañana / Tarde / Noche
);

CREATE TABLE semestres (
    id      SERIAL PRIMARY KEY,
    numero  SMALLINT NOT NULL UNIQUE CHECK (numero BETWEEN 1 AND 12),
    nombre  VARCHAR(40) NOT NULL
);

-- ---------------------------------------------------------------------
-- Usuarios y roles (F02: Dirección y Coordinación de Prácticas = Administrador;
-- acceso con usuario y contraseña)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(80)  NOT NULL,
    apellido       VARCHAR(80)  NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,   -- DG: obligatorio para notificar por correo
    password_hash  VARCHAR(255) NOT NULL,
    rol            VARCHAR(20)  NOT NULL CHECK (rol IN ('admin', 'estudiante')),
    cargo          VARCHAR(80),                    -- p. ej. "Dirección", "Coordinación de Prácticas"
    sede_id        INT REFERENCES sedes(id),
    turno_id       INT REFERENCES turnos(id),
    activo         BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en      TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT estudiante_con_sede_turno CHECK (
        rol <> 'estudiante' OR (sede_id IS NOT NULL AND turno_id IS NOT NULL)
    )
);

-- F02: el semestre siguiente solo se habilita cuando aprobó el actual.
-- DG: lo marca el administrador.
CREATE TABLE avance_estudiante (
    id                SERIAL PRIMARY KEY,
    estudiante_id     INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    semestre_id       INT NOT NULL REFERENCES semestres(id),
    estado            VARCHAR(10) NOT NULL DEFAULT 'cursando'
                      CHECK (estado IN ('cursando', 'aprobado')),
    aprobado_por      INT REFERENCES usuarios(id),
    fecha_aprobacion  TIMESTAMP,
    UNIQUE (estudiante_id, semestre_id)
);

-- F02: prerrequisitos (curso de bioseguridad + habilitación DENERS)
CREATE TABLE prerrequisitos_estudiante (
    estudiante_id          INT PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
    bioseguridad_aprobado  BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_bioseguridad     DATE,
    deners_habilitado      BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_deners           DATE,
    actualizado_por        INT REFERENCES usuarios(id),
    actualizado_en         TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Libreta Digital de Prácticas (F01 + F02: es de CONSULTA; el estudiante
-- no carga datos; el instructor firma en físico)
-- ---------------------------------------------------------------------
CREATE TABLE procedimientos (
    id           SERIAL PRIMARY KEY,
    semestre_id  INT NOT NULL REFERENCES semestres(id),
    area         VARCHAR(80) NOT NULL,             -- F01: organizados por áreas de atención
    titulo       VARCHAR(150) NOT NULL,
    descripcion  TEXT,
    pasos        TEXT,                             -- un paso por línea
    activo       BOOLEAN NOT NULL DEFAULT TRUE
);

-- DG: el administrador refleja en el sistema la firma física del instructor
CREATE TABLE practicas_realizadas (
    id                 SERIAL PRIMARY KEY,
    estudiante_id      INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    procedimiento_id   INT NOT NULL REFERENCES procedimientos(id) ON DELETE CASCADE,
    fecha_realizacion  DATE NOT NULL,
    instructor         VARCHAR(120) NOT NULL,      -- quien firmó en la libreta física
    registrado_por     INT NOT NULL REFERENCES usuarios(id),
    fecha_registro     TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (estudiante_id, procedimiento_id)
);

-- ---------------------------------------------------------------------
-- Documentos de consulta (F01: reglamentos, manuales PAE, guías, protocolos)
-- F02: no existe duplicidad entre reglamento, manual e instructivos
-- ---------------------------------------------------------------------
CREATE TABLE documentos (
    id           SERIAL PRIMARY KEY,
    titulo       VARCHAR(150) NOT NULL,
    categoria    VARCHAR(30) NOT NULL CHECK (categoria IN (
                    'reglamento_practicas', 'reglamento_simulacion',
                    'pae_hospitalario', 'pae_comunitario',
                    'guia_procedimientos', 'protocolo_bioseguridad')),
    area         VARCHAR(80),
    descripcion  TEXT,
    archivo      VARCHAR(255),                     -- nombre interno del PDF; NULL = pendiente de carga
    nombre_original VARCHAR(255),
    version      VARCHAR(20) NOT NULL DEFAULT '1.0',
    publicado    BOOLEAN NOT NULL DEFAULT TRUE,
    subido_por   INT REFERENCES usuarios(id),
    fecha        TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Calendario académico (F01 + F02: parciales, finales y avisos de cambios)
-- Filtros NULL = aplica a todos
-- ---------------------------------------------------------------------
CREATE TABLE eventos_calendario (
    id             SERIAL PRIMARY KEY,
    titulo         VARCHAR(150) NOT NULL,
    tipo           VARCHAR(20) NOT NULL CHECK (tipo IN (
                     'parcial', 'final', 'practica', 'extension', 'investigacion', 'otro')),
    descripcion    TEXT,
    fecha_inicio   TIMESTAMP NOT NULL,
    fecha_fin      TIMESTAMP,
    sede_id        INT REFERENCES sedes(id),
    turno_id       INT REFERENCES turnos(id),
    semestre_id    INT REFERENCES semestres(id),
    creado_por     INT REFERENCES usuarios(id),
    actualizado_en TIMESTAMP NOT NULL DEFAULT NOW(),
    CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
);

-- ---------------------------------------------------------------------
-- Notificaciones dobles (DG: dentro de la plataforma + por correo)
-- ---------------------------------------------------------------------
CREATE TABLE notificaciones (
    id                  SERIAL PRIMARY KEY,
    usuario_id          INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    titulo              VARCHAR(150) NOT NULL,
    mensaje             TEXT NOT NULL,
    enlace              VARCHAR(255),
    leida               BOOLEAN NOT NULL DEFAULT FALSE,
    fecha               TIMESTAMP NOT NULL DEFAULT NOW(),
    correo_enviado      BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_envio_correo  TIMESTAMP,
    intentos_correo     SMALLINT NOT NULL DEFAULT 0,
    error_correo        TEXT
);

CREATE INDEX idx_notif_usuario      ON notificaciones (usuario_id, leida);
CREATE INDEX idx_notif_correo_pend  ON notificaciones (correo_enviado) WHERE correo_enviado = FALSE;
CREATE INDEX idx_eventos_fecha      ON eventos_calendario (fecha_inicio);
CREATE INDEX idx_proc_semestre      ON procedimientos (semestre_id);
CREATE INDEX idx_usuarios_sede      ON usuarios (sede_id, turno_id) WHERE rol = 'estudiante';

-- ---------------------------------------------------------------------
-- Vista de apoyo: estudiante + semestre actual + habilitación de prácticas
-- ---------------------------------------------------------------------
CREATE VIEW v_estudiantes AS
SELECT u.id, u.nombre, u.apellido, u.email, u.activo,
       s.nombre  AS sede,   u.sede_id,
       t.nombre  AS turno,  u.turno_id,
       (SELECT MAX(se.numero) FROM avance_estudiante a
          JOIN semestres se ON se.id = a.semestre_id
         WHERE a.estudiante_id = u.id)                       AS semestre_actual,
       COALESCE(p.bioseguridad_aprobado, FALSE)              AS bioseguridad_aprobado,
       COALESCE(p.deners_habilitado, FALSE)                  AS deners_habilitado,
       COALESCE(p.bioseguridad_aprobado AND p.deners_habilitado, FALSE) AS habilitado_practicas
  FROM usuarios u
  JOIN sedes  s ON s.id = u.sede_id
  JOIN turnos t ON t.id = u.turno_id
  LEFT JOIN prerrequisitos_estudiante p ON p.estudiante_id = u.id
 WHERE u.rol = 'estudiante';

-- =====================================================================
--  DATOS INICIALES Y DE PRUEBA
--  Los procedimientos y documentos son EJEMPLOS para probar el sistema.
--  El contenido oficial se cargará tras la visita técnica (pendiente F02).
-- =====================================================================
INSERT INTO sedes (nombre) VALUES ('Asunción');
INSERT INTO turnos (nombre) VALUES ('Mañana'), ('Tarde'), ('Noche');
INSERT INTO semestres (numero, nombre)
SELECT n, n || '.º semestre' FROM generate_series(1, 8) AS n;

-- Contraseñas de prueba (cambiarlas al ingresar):
--   Administradores: Admin2026!     Estudiantes: Estudiante2026!
INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, cargo, sede_id, turno_id) VALUES
 ('Dirección',    'de Enfermería', 'direccion@enfermeria.test',    crypt('Admin2026!', gen_salt('bf', 10)), 'admin', 'Dirección de Carrera', NULL, NULL),
 ('Coordinación', 'de Prácticas',  'coordinacion@enfermeria.test', crypt('Admin2026!', gen_salt('bf', 10)), 'admin', 'Coordinación de Prácticas', NULL, NULL),
 ('Ana',   'Benítez',  'ana.benitez@enfermeria.test',  crypt('Estudiante2026!', gen_salt('bf', 10)), 'estudiante', NULL, 1, 1),
 ('Luis',  'Giménez',  'luis.gimenez@enfermeria.test', crypt('Estudiante2026!', gen_salt('bf', 10)), 'estudiante', NULL, 1, 3);

-- Ana: aprobó 1.º y 2.º, cursa 3.º, habilitada para prácticas
INSERT INTO avance_estudiante (estudiante_id, semestre_id, estado, aprobado_por, fecha_aprobacion) VALUES
 (3, 1, 'aprobado', 2, '2025-07-10'), (3, 2, 'aprobado', 2, '2025-12-12');
INSERT INTO avance_estudiante (estudiante_id, semestre_id) VALUES (3, 3), (4, 1);

INSERT INTO prerrequisitos_estudiante VALUES
 (3, TRUE, '2025-03-15', TRUE, '2025-04-20', 2, NOW()),
 (4, TRUE, '2026-03-20', FALSE, NULL, 2, NOW());

INSERT INTO procedimientos (semestre_id, area, titulo, descripcion, pasos) VALUES
 (1, 'Fundamentos', 'Lavado de manos clínico', 'Técnica de higiene de manos antes y después del contacto con el paciente.',
  E'Retirar anillos, reloj y pulseras\nMojar manos y aplicar jabón antiséptico\nFrotar palmas, dorsos, espacios interdigitales y uñas\nEnjuagar desde la punta de los dedos hacia la muñeca\nSecar con toalla descartable y cerrar el grifo con la misma toalla'),
 (1, 'Fundamentos', 'Control de signos vitales', 'Medición de temperatura, pulso, respiración y presión arterial.',
  E'Preparar el material y explicar el procedimiento al paciente\nRealizar higiene de manos\nMedir temperatura, pulso y frecuencia respiratoria\nMedir presión arterial con la técnica adecuada\nRegistrar los valores y comunicar alteraciones'),
 (1, 'Fundamentos', 'Tendido de cama hospitalaria', 'Preparación de la unidad del paciente.',
  E'Reunir la ropa de cama necesaria\nRealizar higiene de manos y colocar guantes si corresponde\nTender la sábana de abajo y la zalea\nColocar sábana de arriba, frazada y funda\nDejar la unidad ordenada'),
 (2, 'Clínica médica', 'Administración de medicamentos por vía oral', 'Aplicación de los "correctos" en la administración de medicamentos.',
  E'Verificar la indicación médica\nComprobar paciente, medicamento, dosis, vía y horario correctos\nPreparar el medicamento\nAdministrar y acompañar la toma\nRegistrar la administración'),
 (2, 'Clínica médica', 'Administración de medicamentos por vía intramuscular', 'Técnica de inyección intramuscular segura.',
  E'Verificar la indicación médica\nPreparar el medicamento con técnica aséptica\nSeleccionar y delimitar el sitio de punción\nAplicar la inyección y descartar el material en el contenedor\nObservar reacciones y registrar'),
 (3, 'Clínica quirúrgica', 'Curación de herida quirúrgica', 'Limpieza y cobertura de heridas con técnica estéril.',
  E'Preparar el campo y el material estéril\nRetirar el apósito sucio con guantes de procedimiento\nLimpiar la herida de lo más limpio a lo menos limpio\nValorar signos de infección\nColocar apósito estéril y registrar'),
 (3, 'Clínica quirúrgica', 'Colocación de vía venosa periférica', 'Canalización de vena periférica.',
  E'Verificar la indicación y preparar el material\nSeleccionar la vena y colocar el torniquete\nRealizar antisepsia de la zona\nInsertar el catéter y fijarlo\nRegistrar fecha, calibre y sitio'),
 (3, 'Clínica quirúrgica', 'Cateterismo vesical', 'Inserción de sonda vesical con técnica estéril.',
  E'Explicar el procedimiento y preservar la intimidad\nPreparar el campo estéril\nRealizar higiene perineal\nInsertar la sonda y conectar a la bolsa colectora\nFijar la sonda y registrar'),
 (4, 'Salud comunitaria', 'Valoración de enfermería en el hogar', 'Aplicación del PAE comunitario en visita domiciliaria.',
  E'Planificar la visita\nRealizar la valoración familiar y del entorno\nIdentificar diagnósticos de enfermería\nAcordar intervenciones con la familia\nRegistrar y programar seguimiento');

-- Ana ya tiene prácticas firmadas en papel y registradas por Coordinación
INSERT INTO practicas_realizadas (estudiante_id, procedimiento_id, fecha_realizacion, instructor, registrado_por) VALUES
 (3, 1, '2025-04-02', 'Lic. Instructora de prueba', 2),
 (3, 2, '2025-04-09', 'Lic. Instructora de prueba', 2),
 (3, 3, '2025-05-14', 'Lic. Instructora de prueba', 2),
 (3, 4, '2025-09-03', 'Lic. Instructor de prueba', 2),
 (3, 6, '2026-09-18', 'Lic. Instructor de prueba', 2);

-- Documentos: quedan "pendientes de carga" hasta subir el PDF oficial
INSERT INTO documentos (titulo, categoria, area, descripcion, subido_por) VALUES
 ('Reglamento de Prácticas Profesionales Externas', 'reglamento_practicas',   NULL, 'Normas para las prácticas en instituciones externas.', 1),
 ('Reglamento de la Sala de Simulación',           'reglamento_simulacion',  NULL, 'Normas de uso, bioseguridad y responsabilidades.', 1),
 ('Manual PAE Hospitalario',                       'pae_hospitalario',       'Hospitalaria', 'Diagnósticos, intervenciones y planes de cuidados.', 1),
 ('Manual PAE Comunitario',                        'pae_comunitario',        'Comunitaria', 'Guía para prácticas en atención primaria.', 1),
 ('Guía de Procedimientos de Enfermería',          'guia_procedimientos',    'General', 'Procedimientos organizados por áreas de atención.', 1),
 ('Protocolo de Bioseguridad y Seguridad del Paciente', 'protocolo_bioseguridad', NULL, 'Consulta rápida durante las prácticas.', 1);

INSERT INTO eventos_calendario (titulo, tipo, descripcion, fecha_inicio, fecha_fin, semestre_id, creado_por) VALUES
 ('Primer parcial - Enfermería Clínica',  'parcial',  'Aula 3, Sede Asunción', CURRENT_DATE + INTERVAL '5 days 08:00',  NULL, 3, 1),
 ('Rotación en Clínica Quirúrgica',       'practica', 'Inicio de rotación', CURRENT_DATE + INTERVAL '9 days 07:00', CURRENT_DATE + INTERVAL '30 days 12:00', 3, 2),
 ('Examen final - Fundamentos',           'final',    NULL, CURRENT_DATE + INTERVAL '20 days 18:30', NULL, 1, 1),
 ('Jornada de extensión comunitaria',     'extension', 'Actividad abierta a todos los semestres', CURRENT_DATE + INTERVAL '12 days 09:00', NULL, NULL, 2);

INSERT INTO notificaciones (usuario_id, titulo, mensaje, enlace) VALUES
 (3, 'Bienvenida a la plataforma', 'Ya podés consultar tu Libreta Digital de Prácticas.', 'libreta.php');
