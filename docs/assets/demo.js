/* =====================================================================
   Plataforma Académica · Carrera de Enfermería (UniNorte)
   VERSIÓN DEMO para GitHub Pages: funciona entera en el navegador.
   Los datos se guardan en el navegador de cada persona (localStorage).
   La versión completa usa PHP + PostgreSQL (ver el repositorio).
   ===================================================================== */
(function () {
  'use strict';

  // ------------------------------------------------------------------
  // Utilidades
  // ------------------------------------------------------------------
  var CLAVE = 'enf-demo-v1';
  var MESES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var MESES_C = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  var DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
  var TIPOS = { parcial: 'Examen parcial', final: 'Examen final', practica: 'Práctica', extension: 'Extensión', investigacion: 'Investigación', otro: 'Otro' };
  var CATS = {
    reglamento_practicas: ['Reglamento de Prácticas Externas', 'escudo'],
    reglamento_simulacion: ['Reglamento de Sala de Simulación', 'monitor'],
    pae_hospitalario: ['Manual PAE Hospitalario', 'hospital'],
    pae_comunitario: ['Manual PAE Comunitario', 'casa'],
    guia_procedimientos: ['Guías de Procedimientos', 'libro'],
    protocolo_bioseguridad: ['Protocolos de Bioseguridad', 'escudo_check']
  };

  function e(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function hoyISO() { var d = new Date(); return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function ahoraISO() { var d = new Date(); return hoyISO() + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }
  function sumarDias(n, hora) {
    var d = new Date(); d.setDate(d.getDate() + n);
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + (hora || '00:00');
  }
  function parse(f) {
    if (!f) return null;
    var p = f.split(/[-T:]/);
    return new Date(+p[0], +p[1] - 1, +p[2], +(p[3] || 0), +(p[4] || 0));
  }
  function fechaLarga(f, hora) {
    var d = parse(f); if (!d) return '—';
    var t = DIAS[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth() + 1];
    if (d.getFullYear() !== new Date().getFullYear()) t += ' de ' + d.getFullYear();
    if (hora && (d.getHours() || d.getMinutes())) t += ', ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ' h';
    return t;
  }
  function fechaCorta(f) { var d = parse(f); return d ? d.getDate() + ' ' + MESES_C[d.getMonth() + 1] + ' ' + d.getFullYear() : '—'; }
  function hace(f) {
    var s = (Date.now() - parse(f).getTime()) / 1000;
    if (s < 60) return 'hace un momento';
    if (s < 3600) return 'hace ' + Math.floor(s / 60) + ' min';
    if (s < 86400) return 'hace ' + Math.floor(s / 3600) + ' h';
    if (s < 604800) return 'hace ' + Math.floor(s / 86400) + ' d';
    return fechaCorta(f);
  }
  function iniciales(a, b) { return ((a || '').charAt(0) + (b || '').charAt(0)).toUpperCase(); }
  function pct(h, t) { return t ? Math.round(h * 100 / t) : 0; }

  // ------------------------------------------------------------------
  // Íconos
  // ------------------------------------------------------------------
  var IC = {
    inicio: '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    libreta: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 2.5h6v3H9zM9 10h6M9 14h6M9 18h3"/>',
    calendario: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h2M14 14h2M8 17h2"/>',
    documento: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    campana: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/>',
    usuario: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    usuarios: '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 4.1a4 4 0 0 1 0 7.8M22 21a7 7 0 0 0-4-6.3"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7"/>',
    check_circulo: '<circle cx="12" cy="12" r="9.5"/><path d="m8 12.5 3 3 5-6"/>',
    candado: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    escudo: '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
    escudo_check: '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
    salir: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    sol: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    luna: '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
    mas: '<path d="M12 5v14M5 12h14"/>',
    flecha_der: '<path d="m9 6 6 6-6 6"/>',
    flecha_izq: '<path d="m15 6-6 6 6 6"/>',
    alerta: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    correo: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    pulso: '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
    monitor: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
    hospital: '<path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M12 7v6M9 10h6M10 21v-4h4v4M2 21h20"/>',
    casa: '<path d="m3 11 9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5"/>',
    libro: '<path d="M4 19V5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2 2 2 0 0 0 2 2h13"/>',
    buscar: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    editar: '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
    basura: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
    reloj: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    enviar: '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
    estetoscopio: '<path d="M6 3H5v6a5 5 0 0 0 10 0V3h-1M10 14v1a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="11" r="2"/>',
    birrete: '<path d="M2 9 12 4l10 5-10 5zM6 11v5c3 2 9 2 12 0v-5M22 9v6"/>',
    ojo: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    filtro: '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>'
  };
  function ico(n, c) {
    return '<svg class="' + (c || 'ico') + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (IC[n] || IC.documento) + '</svg>';
  }

  // ------------------------------------------------------------------
  // Datos de ejemplo (mismos que sql/enfermeria_db.sql)
  // ------------------------------------------------------------------
  function datosIniciales() {
    var P = function (sem, area, titulo, desc, pasos) { return { semestre: sem, area: area, titulo: titulo, descripcion: desc, pasos: pasos, activo: true }; };
    var procs = [
      P(1, 'Fundamentos', 'Lavado de manos clínico', 'Técnica de higiene de manos antes y después del contacto con el paciente.',
        ['Retirar anillos, reloj y pulseras', 'Mojar manos y aplicar jabón antiséptico', 'Frotar palmas, dorsos, espacios interdigitales y uñas', 'Enjuagar desde la punta de los dedos hacia la muñeca', 'Secar con toalla descartable y cerrar el grifo con la misma toalla']),
      P(1, 'Fundamentos', 'Control de signos vitales', 'Medición de temperatura, pulso, respiración y presión arterial.',
        ['Preparar el material y explicar el procedimiento al paciente', 'Realizar higiene de manos', 'Medir temperatura, pulso y frecuencia respiratoria', 'Medir presión arterial con la técnica adecuada', 'Registrar los valores y comunicar alteraciones']),
      P(1, 'Fundamentos', 'Tendido de cama hospitalaria', 'Preparación de la unidad del paciente.',
        ['Reunir la ropa de cama necesaria', 'Realizar higiene de manos y colocar guantes si corresponde', 'Tender la sábana de abajo y la zalea', 'Colocar sábana de arriba, frazada y funda', 'Dejar la unidad ordenada']),
      P(2, 'Clínica médica', 'Administración de medicamentos por vía oral', 'Aplicación de los "correctos" en la administración de medicamentos.',
        ['Verificar la indicación médica', 'Comprobar paciente, medicamento, dosis, vía y horario correctos', 'Preparar el medicamento', 'Administrar y acompañar la toma', 'Registrar la administración']),
      P(2, 'Clínica médica', 'Administración de medicamentos por vía intramuscular', 'Técnica de inyección intramuscular segura.',
        ['Verificar la indicación médica', 'Preparar el medicamento con técnica aséptica', 'Seleccionar y delimitar el sitio de punción', 'Aplicar la inyección y descartar el material en el contenedor', 'Observar reacciones y registrar']),
      P(3, 'Clínica quirúrgica', 'Curación de herida quirúrgica', 'Limpieza y cobertura de heridas con técnica estéril.',
        ['Preparar el campo y el material estéril', 'Retirar el apósito sucio con guantes de procedimiento', 'Limpiar la herida de lo más limpio a lo menos limpio', 'Valorar signos de infección', 'Colocar apósito estéril y registrar']),
      P(3, 'Clínica quirúrgica', 'Colocación de vía venosa periférica', 'Canalización de vena periférica.',
        ['Verificar la indicación y preparar el material', 'Seleccionar la vena y colocar el torniquete', 'Realizar antisepsia de la zona', 'Insertar el catéter y fijarlo', 'Registrar fecha, calibre y sitio']),
      P(3, 'Clínica quirúrgica', 'Cateterismo vesical', 'Inserción de sonda vesical con técnica estéril.',
        ['Explicar el procedimiento y preservar la intimidad', 'Preparar el campo estéril', 'Realizar higiene perineal', 'Insertar la sonda y conectar a la bolsa colectora', 'Fijar la sonda y registrar']),
      P(4, 'Salud comunitaria', 'Valoración de enfermería en el hogar', 'Aplicación del PAE comunitario en visita domiciliaria.',
        ['Planificar la visita', 'Realizar la valoración familiar y del entorno', 'Identificar diagnósticos de enfermería', 'Acordar intervenciones con la familia', 'Registrar y programar seguimiento'])
    ];
    procs.forEach(function (p, i) { p.id = i + 1; });
    var D = function (t, c, a, d) { return { titulo: t, categoria: c, area: a, descripcion: d, version: '1.0', publicado: true, archivo: null, nombre: null, fecha: hoyISO() }; };
    var docs = [
      D('Reglamento de Prácticas Profesionales Externas', 'reglamento_practicas', null, 'Normas para las prácticas en instituciones externas.'),
      D('Reglamento de la Sala de Simulación', 'reglamento_simulacion', null, 'Normas de uso, bioseguridad y responsabilidades.'),
      D('Manual PAE Hospitalario', 'pae_hospitalario', 'Hospitalaria', 'Diagnósticos, intervenciones y planes de cuidados.'),
      D('Manual PAE Comunitario', 'pae_comunitario', 'Comunitaria', 'Guía para prácticas en atención primaria.'),
      D('Guía de Procedimientos de Enfermería', 'guia_procedimientos', 'General', 'Procedimientos organizados por áreas de atención.'),
      D('Protocolo de Bioseguridad y Seguridad del Paciente', 'protocolo_bioseguridad', null, 'Consulta rápida durante las prácticas.')
    ];
    docs.forEach(function (d, i) { d.id = i + 1; });
    return {
      sedes: [{ id: 1, nombre: 'Asunción' }],
      turnos: [{ id: 1, nombre: 'Mañana' }, { id: 2, nombre: 'Tarde' }, { id: 3, nombre: 'Noche' }],
      semestres: [1, 2, 3, 4, 5, 6, 7, 8].map(function (n) { return { id: n, numero: n, nombre: n + '.º semestre' }; }),
      usuarios: [
        { id: 1, nombre: 'Dirección', apellido: 'de Enfermería', email: 'direccion@enfermeria.test', clave: 'Admin2026!', rol: 'admin', cargo: 'Dirección de Carrera', activo: true },
        { id: 2, nombre: 'Coordinación', apellido: 'de Prácticas', email: 'coordinacion@enfermeria.test', clave: 'Admin2026!', rol: 'admin', cargo: 'Coordinación de Prácticas', activo: true },
        { id: 3, nombre: 'Ana', apellido: 'Benítez', email: 'ana.benitez@enfermeria.test', clave: 'Estudiante2026!', rol: 'estudiante', sede: 1, turno: 1, activo: true },
        { id: 4, nombre: 'Luis', apellido: 'Giménez', email: 'luis.gimenez@enfermeria.test', clave: 'Estudiante2026!', rol: 'estudiante', sede: 1, turno: 3, activo: true }
      ],
      avance: [
        { id: 1, est: 3, sem: 1, estado: 'aprobado', fecha: '2025-07-10' },
        { id: 2, est: 3, sem: 2, estado: 'aprobado', fecha: '2025-12-12' },
        { id: 3, est: 3, sem: 3, estado: 'cursando', fecha: null },
        { id: 4, est: 4, sem: 1, estado: 'cursando', fecha: null }
      ],
      prereq: [
        { est: 3, bio: true, fbio: '2025-03-15', den: true, fden: '2025-04-20' },
        { est: 4, bio: true, fbio: '2026-03-20', den: false, fden: null }
      ],
      procedimientos: procs,
      practicas: [
        { id: 1, est: 3, proc: 1, fecha: '2025-04-02', instructor: 'Lic. Instructora de prueba', registro: '2025-04-03' },
        { id: 2, est: 3, proc: 2, fecha: '2025-04-09', instructor: 'Lic. Instructora de prueba', registro: '2025-04-10' },
        { id: 3, est: 3, proc: 3, fecha: '2025-05-14', instructor: 'Lic. Instructora de prueba', registro: '2025-05-15' },
        { id: 4, est: 3, proc: 4, fecha: '2025-09-03', instructor: 'Lic. Instructor de prueba', registro: '2025-09-04' },
        { id: 5, est: 3, proc: 6, fecha: '2026-09-18', instructor: 'Lic. Instructor de prueba', registro: '2026-09-19' }
      ],
      documentos: docs,
      eventos: [
        { id: 1, titulo: 'Primer parcial - Enfermería Clínica', tipo: 'parcial', descripcion: 'Aula 3, Sede Asunción', inicio: sumarDias(5, '08:00'), fin: null, sede: null, turno: null, sem: 3 },
        { id: 2, titulo: 'Rotación en Clínica Quirúrgica', tipo: 'practica', descripcion: 'Inicio de rotación', inicio: sumarDias(9, '07:00'), fin: sumarDias(30, '12:00'), sede: null, turno: null, sem: 3 },
        { id: 3, titulo: 'Examen final - Fundamentos', tipo: 'final', descripcion: '', inicio: sumarDias(20, '18:30'), fin: null, sede: null, turno: null, sem: 1 },
        { id: 4, titulo: 'Jornada de extensión comunitaria', tipo: 'extension', descripcion: 'Actividad abierta a todos los semestres', inicio: sumarDias(12, '09:00'), fin: null, sede: null, turno: null, sem: null }
      ],
      notificaciones: [
        { id: 1, usuario: 3, titulo: 'Bienvenida a la plataforma', mensaje: 'Ya podés consultar tu Libreta Digital de Prácticas.', enlace: '#/libreta', leida: false, fecha: ahoraISO() }
      ],
      sesion: null,
      intentos: 0
    };
  }

  var memoria = null;
  function cargar() {
    try { var t = localStorage.getItem(CLAVE); if (t) return JSON.parse(t); } catch (x) {}
    return memoria || datosIniciales();
  }
  var DB = cargar();
  function guardar() {
    memoria = DB;
    try { localStorage.setItem(CLAVE, JSON.stringify(DB)); return true; } catch (x) { return false; }
  }
  function nuevoId(lista) { return lista.reduce(function (m, x) { return Math.max(m, x.id || 0); }, 0) + 1; }
  function porId(lista, id) { id = +id; for (var i = 0; i < lista.length; i++) if (lista[i].id === id) return lista[i]; return null; }

  // ------------------------------------------------------------------
  // Consultas
  // ------------------------------------------------------------------
  function yo() { return DB.sesion ? porId(DB.usuarios, DB.sesion) : null; }
  function esAdmin() { var u = yo(); return u && u.rol === 'admin'; }
  function avanceDe(id) {
    return DB.avance.filter(function (a) { return a.est === id; }).sort(function (a, b) { return a.sem - b.sem; });
  }
  function semActual(id) { var a = avanceDe(id); return a.length ? a[a.length - 1].sem : 0; }
  function prereqDe(id) {
    for (var i = 0; i < DB.prereq.length; i++) if (DB.prereq[i].est === id) return DB.prereq[i];
    var p = { est: id, bio: false, fbio: null, den: false, fden: null }; DB.prereq.push(p); return p;
  }
  function habilitado(id) { var p = prereqDe(id); return !!(p.bio && p.den); }
  function nombreDe(lista, id) { var x = porId(lista, id); return x ? x.nombre : ''; }
  function estudiantes() { return DB.usuarios.filter(function (u) { return u.rol === 'estudiante'; }); }
  function practicaDe(est, proc) {
    for (var i = 0; i < DB.practicas.length; i++) if (DB.practicas[i].est === est && DB.practicas[i].proc === proc) return DB.practicas[i];
    return null;
  }
  function progreso(est, sem) {
    var ps = DB.procedimientos.filter(function (p) { return p.activo && p.semestre === sem; });
    var h = ps.filter(function (p) { return practicaDe(est, p.id); }).length;
    return { total: ps.length, hechas: h };
  }
  function sinLeer(id) { return DB.notificaciones.filter(function (n) { return n.usuario === id && !n.leida; }).length; }
  function eventosPara(u, desde, hasta, limite) {
    var act = u.rol === 'estudiante' ? semActual(u.id) : 0;
    var l = DB.eventos.filter(function (ev) {
      var fin = (ev.fin || ev.inicio).slice(0, 10);
      if (fin < desde || ev.inicio.slice(0, 10) >= hasta) return false;
      if (u.rol === 'estudiante') {
        if (ev.sede && ev.sede !== u.sede) return false;
        if (ev.turno && ev.turno !== u.turno) return false;
        if (ev.sem && ev.sem !== act) return false;
      }
      return true;
    }).sort(function (a, b) { return a.inicio < b.inicio ? -1 : 1; });
    return limite ? l.slice(0, limite) : l;
  }
  function destinatarios(sede, turno, sem) {
    return estudiantes().filter(function (s) {
      return s.activo && (!sede || s.sede === sede) && (!turno || s.turno === turno) && (!sem || semActual(s.id) === sem);
    }).map(function (s) { return s.id; });
  }
  function notificar(ids, titulo, mensaje, enlace) {
    ids = ids.filter(function (v, i, a) { return a.indexOf(v) === i; });
    ids.forEach(function (id) {
      DB.notificaciones.push({ id: nuevoId(DB.notificaciones), usuario: id, titulo: titulo, mensaje: mensaje, enlace: enlace || null, leida: false, fecha: ahoraISO() });
    });
    return ' Se notificó a ' + ids.length + ' estudiante(s).';
  }

  // ------------------------------------------------------------------
  // Mensajes, navegación y tema
  // ------------------------------------------------------------------
  var avisos = [];
  function flash(tipo, msg) { avisos.push({ tipo: tipo, msg: msg }); }
  function ir(ruta) { if (location.hash === ruta) render(); else location.hash = ruta; }
  function ruta() {
    var h = (location.hash || '#/inicio').slice(1);
    var partes = h.split('?');
    var q = {};
    (partes[1] || '').split('&').forEach(function (kv) { if (kv) { var p = kv.split('='); q[decodeURIComponent(p[0])] = decodeURIComponent(p[1] || ''); } });
    return { path: partes[0] || '/inicio', q: q };
  }
  function botonTema(extra) {
    return '<button class="btn-icono boton-tema ' + (extra || '') + '" type="button" data-tema-btn aria-label="Cambiar modo claro u oscuro">' + ico('luna', 'ico ico-luna') + ico('sol', 'ico ico-sol') + '</button>';
  }

  // ------------------------------------------------------------------
  // Estructura de la página
  // ------------------------------------------------------------------
  function menu() {
    if (esAdmin()) return [
      ['inicio', 'Inicio', 'inicio', '#/inicio'], ['estudiantes', 'Estudiantes', 'usuarios', '#/admin/estudiantes'],
      ['procedimientos', 'Libreta', 'libreta', '#/admin/procedimientos'], ['calendario', 'Calendario', 'calendario', '#/calendario'],
      ['documentos', 'Documentos', 'documento', '#/documentos']];
    return [
      ['inicio', 'Inicio', 'inicio', '#/inicio'], ['libreta', 'Mi Libreta', 'libreta', '#/libreta'],
      ['calendario', 'Calendario', 'calendario', '#/calendario'], ['documentos', 'Documentos', 'documento', '#/documentos'],
      ['perfil', 'Perfil', 'usuario', '#/perfil']];
  }
  function layout(titulo, activo, cuerpo, opc) {
    opc = opc || {};
    var u = yo(), n = sinLeer(u.id);
    var cargo = u.rol === 'admin' ? (u.cargo || 'Administrador') : 'Estudiante de Enfermería';
    var lat = menu().map(function (m) {
      return '<a href="' + m[3] + '" class="' + (m[0] === activo ? 'activo' : '') + '">' + ico(m[2]) + '<span>' + m[1] + '</span></a>';
    }).join('');
    lat += '<a href="#/notificaciones" class="' + (activo === 'notificaciones' ? 'activo' : '') + '">' + ico('campana') + '<span>Notificaciones</span>' + (n ? '<b class="contador">' + n + '</b>' : '') + '</a>';
    if (u.rol === 'admin') lat += '<a href="#/perfil" class="' + (activo === 'perfil' ? 'activo' : '') + '">' + ico('usuario') + '<span>Mi perfil</span></a>';
    var inf = menu().map(function (m) {
      return '<a href="' + m[3] + '" class="' + (m[0] === activo ? 'activo' : '') + '">' + ico(m[2]) + '<span>' + m[1] + '</span></a>';
    }).join('');
    var cabecera = opc.saludo
      ? '<div class="saludo"><div class="avatar avatar-grande">' + e(iniciales(u.nombre, u.apellido)) + '</div><div><h1>' + e(u.nombre + ' ' + u.apellido) + '</h1><p>' + e(cargo) + '</p></div></div>'
      : (opc.volver ? '<a class="btn-icono solo-movil" href="' + opc.volver + '" aria-label="Volver">' + ico('flecha_izq') + '</a>' : '') + '<h1 class="barra-titulo">' + e(titulo) + '</h1>';
    var fl = avisos.map(function (a) {
      return '<div class="aviso aviso-' + a.tipo + '" role="status">' + ico(a.tipo === 'error' ? 'alerta' : 'check_circulo') + '<span>' + e(a.msg) + '</span></div>';
    }).join('');
    avisos = [];
    document.title = titulo + ' · Enfermería UniNorte';
    document.body.className = 'app';
    return '<a class="saltar" href="#contenido">Saltar al contenido</a>' +
      '<aside class="lateral" aria-label="Menú principal"><a class="lateral-marca" href="#/inicio"><span class="logo-insignia"><img src="assets/logo-enfermeria.png" alt="Logo de la Carrera de Enfermería UniNorte"></span>' +
      '<span><strong>Libreta de Prácticas</strong><small>Enfermería · UniNorte</small></span></a><nav class="lateral-nav">' + lat + '</nav>' +
      '<div class="lateral-pie"><div class="avatar">' + e(iniciales(u.nombre, u.apellido)) + '</div><div class="lateral-usuario"><strong>' + e(u.nombre + ' ' + u.apellido) + '</strong><small>' + e(cargo) + '</small></div>' +
      '<button class="btn-icono" type="button" data-salir title="Cerrar sesión" aria-label="Cerrar sesión">' + ico('salir') + '</button></div></aside>' +
      '<div class="marco"><header class="barra ' + (opc.saludo ? 'barra-saludo' : '') + '">' + cabecera +
      '<div class="barra-acciones">' + botonTema() + '<a class="btn-icono campana" href="#/notificaciones" aria-label="Notificaciones">' + ico('campana') + (n ? '<b class="punto">' + (n > 9 ? '9+' : n) + '</b>' : '') + '</a>' +
      '<button class="btn-icono solo-movil" type="button" data-salir aria-label="Cerrar sesión">' + ico('salir') + '</button></div></header>' +
      '<main id="contenido" class="contenido">' + fl + cuerpo + '</main></div>' +
      '<nav class="nav-inferior" aria-label="Navegación">' + inf + '</nav>';
  }

  function filaEvento(ev, admin, acciones) {
    var d = parse(ev.inicio), alc = [];
    if (admin) {
      alc.push(ev.sem ? ev.sem + '.º sem.' : 'Todos los semestres');
      if (ev.turno) alc.push(nombreDe(DB.turnos, ev.turno));
      if (ev.sede) alc.push(nombreDe(DB.sedes, ev.sede));
    }
    var rango = fechaLarga(ev.inicio, true) + (ev.fin ? ' → ' + fechaLarga(ev.fin, true) : '');
    return '<li class="t-' + ev.tipo + '"><div class="fecha-caja"><b>' + d.getDate() + '</b><span>' + MESES_C[d.getMonth() + 1] + '</span></div>' +
      '<div class="lista-cuerpo"><span class="tipo-evento">' + TIPOS[ev.tipo] + '</span><strong>' + e(ev.titulo) + '</strong><small>' + e(rango) + (alc.length ? ' · ' + e(alc.join(' · ')) : '') + '</small>' +
      (ev.descripcion ? '<div class="muy-tenue">' + e(ev.descripcion) + '</div>' : '') + '</div>' + (acciones || '') + '</li>';
  }
  function opciones(lista, sel, vacio) {
    return (vacio ? '<option value="">' + vacio + '</option>' : '') + lista.map(function (x) {
      return '<option value="' + x.id + '"' + (+sel === x.id ? ' selected' : '') + '>' + e(x.nombre) + '</option>';
    }).join('');
  }
  function etiquetaSiNo(v, si) {
    return v ? '<span class="etiqueta-estado e-exito">' + ico('check') + (si || 'Sí') + '</span>' : '<span class="etiqueta-estado e-alerta">' + (si ? 'Pendiente' : 'No') + '</span>';
  }

  // ------------------------------------------------------------------
  // Vistas
  // ------------------------------------------------------------------
  var V = {};

  V.login = function () {
    document.title = 'Iniciar sesión · Enfermería UniNorte';
    document.body.className = 'sin-lateral';
    var err = avisos.map(function (a) { return '<div class="aviso aviso-' + a.tipo + '" role="alert">' + ico('alerta') + '<span>' + e(a.msg) + '</span></div>'; }).join('');
    avisos = [];
    return '<div class="ingreso"><section class="ingreso-arte">' + botonTema('ingreso-tema') +
      '<div class="latido">' + ico('pulso') + '</div><div class="ingreso-logo"><img src="assets/logo-enfermeria.png" alt="Logo oficial de la Carrera de Enfermería, Universidad del Norte"></div>' +
      '<h1>Libreta de Prácticas</h1><span class="pildora">Enfermería</span><p>Plataforma académica de la Carrera de Enfermería · Universidad del Norte</p>' +
      '<svg class="ondas" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 64c180 40 360 56 540 40S900 24 1080 24s300 32 360 48v48H0z"/></svg></section>' +
      '<section class="ingreso-form"><div class="tarjeta"><h2>Iniciar sesión</h2><p class="tenue">Ingresá con el usuario y la contraseña que te entregó la Coordinación de Prácticas.</p>' + err +
      '<form data-accion="login" novalidate><div class="campo"><label for="email">Correo electrónico</label><div class="input-icono">' + ico('correo') +
      '<input id="email" name="email" type="email" autocomplete="username" required placeholder="nombre@uninorte.edu.py"></div></div>' +
      '<div class="campo"><label for="clave">Contraseña</label><div class="input-icono">' + ico('candado') +
      '<input id="clave" name="clave" type="password" autocomplete="current-password" required placeholder="••••••••"></div></div>' +
      '<button class="btn btn-bloque" type="submit">Iniciar sesión</button></form>' +
      '<p class="muy-tenue" style="margin-top:18px;text-align:center">¿Olvidaste tu contraseña o no tenés cuenta?<br>Solicitala a la Coordinación de Prácticas.</p></div></section></div>';
  };

  V.inicio = function () {
    var u = yo();
    if (u.rol === 'admin') {
      var ests = estudiantes();
      var activos = ests.filter(function (s) { return s.activo; });
      var sinHab = activos.filter(function (s) { return !habilitado(s.id); });
      var mes = hoyISO().slice(0, 7);
      var pm = DB.practicas.filter(function (p) { return (p.registro || '').slice(0, 7) === mes; }).length;
      var evs = eventosPara(u, hoyISO(), sumarDias(60).slice(0, 10), 5);
      var pend = sinHab.slice(0, 6).map(function (p) {
        var pr = prereqDe(p.id), falta = [];
        if (!pr.bio) falta.push('Bioseguridad'); if (!pr.den) falta.push('DENERS');
        return '<li><a class="fila-enlace" href="#/admin/estudiante?id=' + p.id + '"><div class="avatar">' + e(iniciales(p.nombre, p.apellido)) + '</div><div class="lista-cuerpo"><strong>' + e(p.apellido + ', ' + p.nombre) + '</strong><small>' + semActual(p.id) + '.º sem. · ' + e(nombreDe(DB.turnos, p.turno)) + ' · ' + falta.join(' y ') + '</small></div>' + ico('flecha_der') + '</a></li>';
      }).join('');
      return layout('Inicio', 'inicio',
        '<div class="cifras"><div class="cifra"><b>' + activos.length + '</b><span>Estudiantes activos</span></div><div class="cifra"><b>' + sinHab.length + '</b><span>Sin habilitación para prácticas</span></div>' +
        '<div class="cifra"><b>' + pm + '</b><span>Prácticas registradas este mes</span></div><div class="cifra"><b>' + DB.notificaciones.length + '</b><span>Avisos enviados</span></div></div>' +
        '<div class="seccion-titulo"><h2>Accesos rápidos</h2></div><div class="mosaicos">' +
        '<a class="mosaico" href="#/admin/estudiantes">' + ico('usuarios') + 'Estudiantes<small>Avance, requisitos y prácticas</small></a>' +
        '<a class="mosaico" href="#/admin/procedimientos">' + ico('libreta') + 'Libreta<small>Procedimientos por semestre</small></a>' +
        '<a class="mosaico" href="#/calendario">' + ico('calendario') + 'Calendario<small>Parciales, finales y avisos</small></a>' +
        '<a class="mosaico" href="#/documentos">' + ico('documento') + 'Documentos<small>Reglamentos, PAE y protocolos</small></a></div>' +
        '<div class="grilla grilla-principal" style="margin-top:22px"><section class="tarjeta"><div class="tarjeta-titulo"><h3>Próximos eventos</h3><a class="btn btn-suave btn-chico" href="#/calendario">' + ico('mas') + 'Nuevo</a></div>' +
        (evs.length ? '' : '<div class="vacio">' + ico('calendario') + 'No hay eventos en los próximos 60 días.</div>') + '<ul class="lista">' + evs.map(function (ev) { return filaEvento(ev, true); }).join('') + '</ul></section>' +
        '<section class="tarjeta"><div class="tarjeta-titulo"><h3>Pendientes de habilitación</h3></div><p class="muy-tenue">Falta el curso de bioseguridad o la habilitación ante la DENERS.</p>' +
        (pend ? '<ul class="lista">' + pend + '</ul>' : '<div class="vacio">' + ico('check_circulo') + 'Todos están habilitados.</div>') + '</section></div>',
        { saludo: true });
    }
    var av = avanceDe(u.id), act = av[av.length - 1], pg = act ? progreso(u.id, act.sem) : { total: 0, hechas: 0 };
    var pr = prereqDe(u.id), n = sinLeer(u.id);
    var evs2 = eventosPara(u, hoyISO(), sumarDias(45).slice(0, 10), 4);
    return layout('Inicio', 'inicio',
      '<section class="heroe"><div class="decoracion">' + ico('estetoscopio') + '</div><div class="heroe-contenido"><h2>' + ico('libreta') + 'Mi Libreta de Prácticas</h2>' +
      '<p>Conocé los procedimientos de tu semestre y seguí tu avance. Tu instructor firma cada práctica en la libreta física.</p>' +
      (act ? '<div class="progreso"><span style="width:' + pct(pg.hechas, pg.total) + '%"></span></div><div class="progreso-texto"><span>' + act.sem + '.º semestre · ' + e(nombreDe(DB.turnos, u.turno)) + '</span><span>' + pg.hechas + ' de ' + pg.total + ' realizadas</span></div>' : '') +
      '<a class="btn" href="#/libreta" style="margin-top:14px">Ver mi libreta ' + ico('flecha_der') + '</a></div></section>' +
      (habilitado(u.id) ? '' : '<div class="aviso aviso-alerta" style="margin-top:16px">' + ico('alerta') + '<span>Todavía no estás habilitado/a para realizar prácticas. ' + (pr.bio ? '' : 'Falta aprobar el curso de bioseguridad. ') + (pr.den ? '' : 'Falta la habilitación ante la DENERS.') + '</span></div>') +
      '<div class="seccion-titulo"><h2>Accesos</h2></div><div class="mosaicos">' +
      '<a class="mosaico" href="#/libreta">' + ico('libreta') + 'Procedimientos</a><a class="mosaico" href="#/calendario">' + ico('calendario') + 'Calendario</a>' +
      '<a class="mosaico" href="#/documentos">' + ico('documento') + 'Reglamentos y manuales</a><a class="mosaico" href="#/documentos?cat=protocolo_bioseguridad">' + ico('escudo_check') + 'Bioseguridad</a>' +
      '<a class="mosaico" href="#/notificaciones">' + ico('campana') + 'Notificaciones' + (n ? '<small>' + n + ' sin leer</small>' : '') + '</a><a class="mosaico" href="#/perfil">' + ico('birrete') + 'Mi avance y requisitos</a>' +
      '<a class="mosaico" href="#/documentos?cat=pae_hospitalario">' + ico('hospital') + 'PAE Hospitalario</a><a class="mosaico" href="#/documentos?cat=pae_comunitario">' + ico('casa') + 'PAE Comunitario</a></div>' +
      '<section class="tarjeta" style="margin-top:22px"><div class="tarjeta-titulo"><h3>Próximas fechas</h3><a href="#/calendario" class="muy-tenue">Ver calendario</a></div>' +
      (evs2.length ? '' : '<div class="vacio">' + ico('calendario') + 'No tenés fechas próximas.</div>') + '<ul class="lista">' + evs2.map(function (ev) { return filaEvento(ev); }).join('') + '</ul></section>',
      { saludo: true });
  };

  V.libreta = function (q) {
    var u = yo(); if (u.rol !== 'estudiante') return ir('#/inicio');
    var av = avanceDe(u.id), vis = {};
    av.forEach(function (a) { vis[a.sem] = a; });
    var sem = +q.sem; if (!vis[sem]) sem = av.length ? av[av.length - 1].sem : 0;
    var tabs = DB.semestres.map(function (s) {
      if (vis[s.id]) return '<a class="pestana ' + (s.id === sem ? 'activa' : '') + '" href="#/libreta?sem=' + s.id + '">' + (vis[s.id].estado === 'aprobado' ? ico('check') : '') + e(s.nombre) + '</a>';
      return '<span class="pestana bloqueada" title="Se habilita al aprobar el semestre anterior">' + ico('candado') + e(s.nombre) + '</span>';
    }).join('');
    if (!sem) return layout('Mi Libreta', 'libreta', '<nav class="pestanas">' + tabs + '</nav><div class="tarjeta bloqueo">' + ico('candado') + '<h3>Todavía no tenés semestres habilitados</h3></div>');
    var procs = DB.procedimientos.filter(function (p) { return p.activo && p.semestre === sem; }).sort(function (a, b) { return (a.area + a.titulo).localeCompare(b.area + b.titulo); });
    var area = null, lista = '';
    procs.forEach(function (p, i) {
      if (p.area !== area) { area = p.area; lista += '<h3 style="margin:' + (i ? '22px' : '0') + ' 0 10px">' + e(area) + '</h3>'; }
      var r = practicaDe(u.id, p.id);
      lista += '<a class="procedimiento" href="#/procedimiento?id=' + p.id + '"><div class="icono-circulo ' + (r ? 'hecho' : '') + '">' + ico(r ? 'check' : 'pulso') + '</div><div class="lista-cuerpo"><strong>' + e(p.titulo) + '</strong>' +
        (r ? '<small>Realizada el ' + fechaCorta(r.fecha) + ' · firmó ' + e(r.instructor) + '</small>' : '<small>' + e(p.descripcion) + '</small>') + '</div>' +
        (r ? '<span class="etiqueta-estado e-exito">' + ico('check') + 'Realizada</span>' : '<span class="etiqueta-estado e-neutro">Pendiente</span>') + '</a>';
    });
    var pg = progreso(u.id, sem), a = vis[sem];
    return layout('Mi Libreta', 'libreta', '<nav class="pestanas" aria-label="Semestres">' + tabs + '</nav><div class="grilla grilla-principal"><div>' +
      (habilitado(u.id) ? '' : '<div class="aviso aviso-alerta">' + ico('alerta') + '<span>Podés consultar los procedimientos, pero para realizar prácticas necesitás el curso de bioseguridad aprobado y la habilitación ante la DENERS.</span></div>') +
      (procs.length ? lista : '<div class="tarjeta vacio">' + ico('libreta') + 'Aún no se cargaron procedimientos para este semestre.</div>') + '</div>' +
      '<aside class="tarjeta"><h3>' + sem + '.º semestre</h3><p class="muy-tenue">' + (a.estado === 'aprobado' ? 'Semestre aprobado el ' + fechaCorta(a.fecha) + '.' : 'Semestre en curso · ' + e(nombreDe(DB.turnos, u.turno)) + ' · ' + e(nombreDe(DB.sedes, u.sede))) + '</p>' +
      '<div class="progreso"><span style="width:' + pct(pg.hechas, pg.total) + '%"></span></div><div class="progreso-texto"><span>' + pct(pg.hechas, pg.total) + '% completado</span><span>' + pg.hechas + '/' + pg.total + '</span></div>' +
      '<hr style="border:0;border-top:1px solid var(--borde);margin:18px 0"><p class="muy-tenue" style="margin:0">La firma de cada práctica la coloca tu instructor en la libreta física. La Coordinación la registra luego en el sistema. Si hubo un error, se corrige de forma presencial.</p></aside></div>');
  };

  V.procedimiento = function (q) {
    var u = yo(), p = porId(DB.procedimientos, q.id), r = null;
    if (p && u.rol === 'estudiante') {
      var ok = avanceDe(u.id).some(function (a) { return a.sem === p.semestre; });
      if (!ok || !p.activo) p = null; else r = practicaDe(u.id, p.id);
    }
    if (!p) { flash('error', 'Ese procedimiento no está disponible.'); return ir(u.rol === 'admin' ? '#/admin/procedimientos' : '#/libreta'); }
    var volver = u.rol === 'admin' ? '#/admin/procedimientos?sem=' + p.semestre : '#/libreta?sem=' + p.semestre;
    var estado = '';
    if (u.rol === 'estudiante') {
      estado = '<aside class="tarjeta"><h3>Estado</h3>' + (r
        ? '<div class="aviso aviso-exito">' + ico('check_circulo') + '<span>Práctica realizada</span></div><p><small>Fecha</small><br><strong>' + fechaLarga(r.fecha) + '</strong></p><p><small>Firmó en la libreta física</small><br><strong>' + e(r.instructor) + '</strong></p><p class="muy-tenue">Registrado por la Coordinación el ' + fechaCorta(r.registro) + '.</p>'
        : '<div class="aviso aviso-info">' + ico('reloj') + '<span>Pendiente de realizar</span></div><p class="muy-tenue">Cuando realices esta práctica, tu instructor la firmará en la libreta física y la Coordinación la marcará aquí.</p>') + '</aside>';
    }
    return layout('Procedimiento', u.rol === 'admin' ? 'procedimientos' : 'libreta',
      '<a href="' + volver + '" class="muy-tenue" style="display:inline-flex;gap:4px;align-items:center;margin-bottom:12px">' + ico('flecha_izq') + 'Volver</a><div class="grilla grilla-principal"><article class="tarjeta">' +
      '<span class="area-etiqueta">' + p.semestre + '.º semestre · ' + e(p.area) + '</span><h2 style="margin-top:6px">' + e(p.titulo) + '</h2>' + (p.descripcion ? '<p class="tenue">' + e(p.descripcion) + '</p>' : '') +
      (p.pasos.length ? '<h3 style="margin-top:18px">Pasos</h3><ol class="pasos">' + p.pasos.map(function (x) { return '<li>' + e(x) + '</li>'; }).join('') + '</ol>' : '') + '</article>' + estado + '</div>',
      { volver: volver });
  };

  V.calendario = function (q) {
    var u = yo(), admin = u.rol === 'admin';
    var m = /^\d{4}-\d{2}$/.test(q.m || '') ? q.m : hoyISO().slice(0, 7);
    var y = +m.slice(0, 4), mo = +m.slice(5, 7);
    var ini = new Date(y, mo - 1, 1), fin = new Date(y, mo, 1);
    var prev = new Date(y, mo - 2, 1), sig = fin;
    var key = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1); };
    var evs = eventosPara(u, m + '-01', key(fin) + '-01');
    var porDia = {};
    evs.forEach(function (ev) {
      var a = parse(ev.inicio.slice(0, 10)), b = parse((ev.fin || ev.inicio).slice(0, 10));
      for (var d = new Date(Math.max(a, ini)); d <= b && d < fin; d.setDate(d.getDate() + 1)) {
        var k = d.getDate(); porDia[k] = porDia[k] || []; if (porDia[k].indexOf(ev.tipo) < 0) porDia[k].push(ev.tipo);
      }
    });
    var celdas = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'].map(function (d) { return '<div class="dia-nombre">' + d + '</div>'; }).join('');
    var vacios = (ini.getDay() + 6) % 7;
    for (var i = 0; i < vacios; i++) celdas += '<div class="dia fuera"></div>';
    var dias = new Date(y, mo, 0).getDate(), hoy = hoyISO();
    for (var dd = 1; dd <= dias; dd++) {
      celdas += '<div class="dia ' + (m + '-' + pad(dd) === hoy ? 'hoy' : '') + '">' + dd +
        (porDia[dd] ? '<div class="marcas">' + porDia[dd].map(function (t) { return '<i class="t-' + t + '" title="' + TIPOS[t] + '"></i>'; }).join('') + '</div>' : '') + '</div>';
    }
    var lista = evs.map(function (ev) {
      var acc = admin ? '<div class="acciones"><a class="btn-icono" href="#/calendario?m=' + m + '&editar=' + ev.id + '" aria-label="Editar">' + ico('editar') + '</a>' +
        '<form data-accion="evento-eliminar" data-confirmar="¿Eliminar el evento? Se avisará a los estudiantes que fue cancelado."><input type="hidden" name="id" value="' + ev.id + '"><button class="btn-icono" type="submit" aria-label="Eliminar">' + ico('basura') + '</button></form></div>' : '';
      return filaEvento(ev, admin, acc);
    }).join('');
    var form = '';
    if (admin) {
      var ed = q.editar ? porId(DB.eventos, q.editar) : null;
      var v = ed || { id: 0, titulo: '', tipo: 'parcial', descripcion: '', inicio: '', fin: '', sede: null, turno: null, sem: null };
      form = '<section class="tarjeta" id="form"><h3>' + (ed ? 'Modificar evento' : 'Nuevo evento') + '</h3>' + (ed ? '<p class="muy-tenue">Al guardar se avisará del cambio a los estudiantes alcanzados.</p>' : '') +
        '<form data-accion="evento-guardar"><input type="hidden" name="id" value="' + v.id + '"><input type="hidden" name="m" value="' + m + '">' +
        '<div class="campo"><label for="titulo">Título</label><input id="titulo" name="titulo" type="text" required maxlength="150" value="' + e(v.titulo) + '"></div>' +
        '<div class="campo"><label for="tipo">Tipo</label><select id="tipo" name="tipo">' + Object.keys(TIPOS).map(function (k) { return '<option value="' + k + '"' + (v.tipo === k ? ' selected' : '') + '>' + TIPOS[k] + '</option>'; }).join('') + '</select></div>' +
        '<div class="form-fila"><div class="campo"><label for="fi">Inicio</label><input id="fi" name="inicio" type="datetime-local" required value="' + e(v.inicio) + '"></div>' +
        '<div class="campo"><label for="ff">Fin <small>(opcional)</small></label><input id="ff" name="fin" type="datetime-local" value="' + e(v.fin || '') + '"></div></div>' +
        '<div class="campo"><label for="desc">Descripción</label><textarea id="desc" name="descripcion" rows="3">' + e(v.descripcion) + '</textarea></div><p class="etiqueta">¿A quiénes aplica?</p>' +
        '<div class="form-fila form-fila-3"><div class="campo"><label for="sem">Semestre</label><select id="sem" name="sem">' + opciones(DB.semestres, v.sem, 'Todos') + '</select></div>' +
        '<div class="campo"><label for="tur">Turno</label><select id="tur" name="turno">' + opciones(DB.turnos, v.turno, 'Todos') + '</select></div>' +
        '<div class="campo"><label for="sed">Sede</label><select id="sed" name="sede">' + opciones(DB.sedes, v.sede, 'Todas') + '</select></div></div>' +
        (ed ? '' : '<label class="casilla"><input type="checkbox" name="notificar" value="1" checked> Notificar a los estudiantes</label>') +
        '<div class="acciones" style="margin-top:8px"><button class="btn" type="submit">' + ico('check') + (ed ? 'Guardar cambios' : 'Crear evento') + '</button>' +
        (ed ? '<a class="btn btn-suave" href="#/calendario?m=' + m + '">Cancelar</a>' : '') + '</div></form></section>';
    }
    return layout('Calendario académico', 'calendario', '<div class="grilla grilla-principal"><section class="tarjeta"><div class="tarjeta-titulo">' +
      '<a class="btn-icono" href="#/calendario?m=' + key(prev) + '" aria-label="Mes anterior">' + ico('flecha_izq') + '</a><h2 style="text-transform:capitalize">' + MESES[mo] + ' ' + y + '</h2>' +
      '<a class="btn-icono" href="#/calendario?m=' + key(sig) + '" aria-label="Mes siguiente">' + ico('flecha_der') + '</a></div><div class="mes">' + celdas + '</div>' +
      '<div class="acciones" style="margin-top:14px">' + Object.keys(TIPOS).map(function (k) { return '<span class="tipo-evento t-' + k + '">' + TIPOS[k] + '</span>'; }).join('') + '</div>' +
      '<h3 style="margin-top:22px">Eventos del mes</h3>' + (evs.length ? '' : '<div class="vacio">' + ico('calendario') + 'No hay eventos este mes.</div>') + '<ul class="lista">' + lista + '</ul></section>' + form + '</div>');
  };

  V.documentos = function (q) {
    var u = yo(), admin = u.rol === 'admin';
    var cat = CATS[q.cat] ? q.cat : null, buscar = (q.q || '').toLowerCase();
    var docs = DB.documentos.filter(function (d) {
      if (!admin && !d.publicado) return false;
      if (cat && d.categoria !== cat) return false;
      if (buscar && (d.titulo + ' ' + (d.descripcion || '') + ' ' + (d.area || '')).toLowerCase().indexOf(buscar) < 0) return false;
      return true;
    });
    var conteo = {};
    DB.documentos.forEach(function (d) { if (admin || d.publicado) conteo[d.categoria] = (conteo[d.categoria] || 0) + 1; });
    var mos = Object.keys(CATS).map(function (k) {
      return '<a class="mosaico" href="#/documentos?cat=' + k + '" style="' + (cat === k ? 'outline:2px solid var(--musgo);background:var(--superficie)' : '') + '">' + ico(CATS[k][1]) + CATS[k][0] + '<small>' + (conteo[k] || 0) + ' documento(s)</small></a>';
    }).join('');
    var lista = docs.map(function (d) {
      return '<li><div class="icono-circulo">' + ico(CATS[d.categoria][1]) + '</div><div class="lista-cuerpo"><strong>' + e(d.titulo) + '</strong><small>' + CATS[d.categoria][0] + (d.area ? ' · ' + e(d.area) : '') + ' · v' + e(d.version) + ' · ' + fechaCorta(d.fecha) + '</small>' +
        (d.descripcion ? '<div class="muy-tenue">' + e(d.descripcion) + '</div>' : '') + (admin && !d.publicado ? '<span class="etiqueta-estado e-alerta">Oculto</span>' : '') + '</div><div class="acciones">' +
        (d.archivo ? '<button class="btn btn-sec btn-chico" type="button" data-abrir-doc="' + d.id + '">' + ico('ojo') + 'Abrir</button>' : '<span class="etiqueta-estado e-neutro">Pendiente de carga</span>') +
        (admin ? '<a class="btn-icono" href="#/documentos?editar=' + d.id + '" aria-label="Editar">' + ico('editar') + '</a><form data-accion="doc-eliminar" data-confirmar="¿Eliminar este documento?"><input type="hidden" name="id" value="' + d.id + '"><button class="btn-icono" type="submit" aria-label="Eliminar">' + ico('basura') + '</button></form>' : '') +
        '</div></li>';
    }).join('');
    var form = '';
    if (admin) {
      var ed = q.editar ? porId(DB.documentos, q.editar) : null;
      var v = ed || { id: 0, titulo: '', categoria: cat || 'reglamento_practicas', area: '', descripcion: '', version: '1.0', publicado: true };
      form = '<section class="tarjeta" id="form"><h3>' + (ed ? 'Editar documento' : 'Subir documento') + '</h3><form data-accion="doc-guardar"><input type="hidden" name="id" value="' + v.id + '">' +
        '<div class="campo"><label for="dt">Título</label><input id="dt" name="titulo" type="text" required value="' + e(v.titulo) + '"></div>' +
        '<div class="campo"><label for="dc">Categoría</label><select id="dc" name="categoria">' + Object.keys(CATS).map(function (k) { return '<option value="' + k + '"' + (v.categoria === k ? ' selected' : '') + '>' + CATS[k][0] + '</option>'; }).join('') + '</select></div>' +
        '<div class="form-fila"><div class="campo"><label for="da">Área <small>(opcional)</small></label><input id="da" name="area" type="text" value="' + e(v.area || '') + '"></div>' +
        '<div class="campo"><label for="dv">Versión</label><input id="dv" name="version" type="text" value="' + e(v.version) + '"></div></div>' +
        '<div class="campo"><label for="dd">Descripción</label><textarea id="dd" name="descripcion" rows="3">' + e(v.descripcion || '') + '</textarea></div>' +
        '<div class="campo"><label for="da2">Archivo PDF <small>(hasta 2 MB)</small></label><input id="da2" name="archivo" type="file" accept="application/pdf"></div>' +
        '<label class="casilla"><input type="checkbox" name="publicado" value="1"' + (v.publicado ? ' checked' : '') + '> Visible para estudiantes</label>' +
        '<label class="casilla"><input type="checkbox" name="notificar" value="1"> Avisar a los estudiantes si se sube un archivo</label>' +
        '<div class="acciones" style="margin-top:8px"><button class="btn" type="submit">' + ico('check') + 'Guardar</button>' + (ed ? '<a class="btn btn-suave" href="#/documentos">Cancelar</a>' : '') + '</div></form></section>';
    }
    return layout('Documentos', 'documentos',
      '<form class="chip-filtros" data-accion="doc-buscar"><input type="hidden" name="cat" value="' + (cat || '') + '"><div class="campo"><label for="q" class="etiqueta">Buscar</label><div class="input-icono">' + ico('buscar') +
      '<input id="q" type="search" name="q" value="' + e(q.q || '') + '" placeholder="Título, área o tema"></div></div><button class="btn btn-suave" type="submit">Buscar</button></form>' +
      '<div class="mosaicos">' + mos + '</div><div class="grilla ' + (admin ? 'grilla-principal' : '') + '" style="margin-top:22px"><section class="tarjeta"><div class="tarjeta-titulo"><h3>' + (cat ? CATS[cat][0] : 'Todos los documentos') + '</h3>' +
      (cat || buscar ? '<a class="muy-tenue" href="#/documentos">Ver todos</a>' : '') + '</div>' + (docs.length ? '' : '<div class="vacio">' + ico('documento') + 'No hay documentos para mostrar.</div>') + '<ul class="lista">' + lista + '</ul></section>' + form + '</div>');
  };

  V.notificaciones = function () {
    var u = yo();
    var lista = DB.notificaciones.filter(function (n) { return n.usuario === u.id; }).sort(function (a, b) { return a.fecha < b.fecha ? 1 : (a.fecha > b.fecha ? -1 : b.id - a.id); });
    var n = lista.filter(function (x) { return !x.leida; }).length;
    var icono = function (t) { return /alendario|Evento|fecha/.test(t) ? 'calendario' : (/ráctica/.test(t) ? 'check_circulo' : (/emestre/.test(t) ? 'birrete' : (/ocumento/.test(t) ? 'documento' : 'campana'))); };
    return layout('Notificaciones', 'notificaciones', '<div style="max-width:760px"><div class="tarjeta-titulo"><p class="tenue" style="margin:0">' + (n ? 'Tenés ' + n + ' sin leer.' : 'Estás al día.') + '</p>' +
      (n ? '<form data-accion="leer-todas"><button class="btn btn-suave btn-chico" type="submit">' + ico('check') + 'Marcar todas como leídas</button></form>' : '') + '</div>' +
      (lista.length ? '' : '<div class="tarjeta vacio">' + ico('campana') + 'Todavía no tenés notificaciones.</div>') +
      lista.map(function (x) {
        return '<article class="notif ' + (x.leida ? '' : 'nueva') + '"><div class="icono-circulo">' + ico(icono(x.titulo)) + '</div><div class="lista-cuerpo"><strong>' + e(x.titulo) + '</strong><p style="margin:2px 0 4px">' + e(x.mensaje) + '</p><small>' + hace(x.fecha) + '</small>' +
          '</div>' +
          (x.enlace ? '<button class="btn-icono" type="button" data-abrir-notif="' + x.id + '" aria-label="Abrir">' + ico('flecha_der') + '</button>' : '') + '</article>';
      }).join('') + '</div>');
  };

  V.perfil = function () {
    var u = yo(), est = u.rol === 'estudiante';
    var cargo = u.rol === 'admin' ? (u.cargo || 'Administrador') : 'Estudiante de Enfermería';
    var extra = '';
    if (est) {
      var pr = prereqDe(u.id);
      extra = '<section class="tarjeta" id="requisitos"><h3>Requisitos para prácticas</h3><ul class="lista">' +
        '<li><div class="icono-circulo ' + (pr.bio ? 'hecho' : '') + '">' + ico('escudo_check') + '</div><div class="lista-cuerpo"><strong>Curso de bioseguridad</strong><small>Requisito institucional</small></div>' + etiquetaSiNo(pr.bio, 'Aprobado') + '</li>' +
        '<li><div class="icono-circulo ' + (pr.den ? 'hecho' : '') + '">' + ico('escudo') + '</div><div class="lista-cuerpo"><strong>Habilitación DENERS</strong><small>Dirección Nacional de Recursos Humanos en Salud</small></div>' + etiquetaSiNo(pr.den, 'Habilitado') + '</li></ul>' +
        '<h3 style="margin-top:18px">Mi avance</h3><ul class="lista">' + avanceDe(u.id).map(function (a) {
          return '<li><div class="icono-circulo ' + (a.estado === 'aprobado' ? 'hecho' : '') + '">' + ico(a.estado === 'aprobado' ? 'check' : 'reloj') + '</div><div class="lista-cuerpo"><strong>' + a.sem + '.º semestre</strong><small>' + (a.estado === 'aprobado' ? 'Aprobado el ' + fechaCorta(a.fecha) : 'En curso') + '</small></div></li>';
        }).join('') + '</ul></section>';
    }
    return layout('Mi perfil', 'perfil', '<div class="grilla grilla-2"><section class="tarjeta"><div style="display:flex;gap:14px;align-items:center;margin-bottom:16px"><div class="avatar avatar-grande" style="background:var(--musgo-200)">' + e(iniciales(u.nombre, u.apellido)) + '</div>' +
      '<div><h2 style="margin:0">' + e(u.nombre + ' ' + u.apellido) + '</h2><small>' + e(cargo) + '</small></div></div><ul class="lista"><li>' + ico('correo') + '<div class="lista-cuerpo"><small>Correo (recibe las notificaciones)</small><strong>' + e(u.email) + '</strong></div></li>' +
      (est ? '<li>' + ico('hospital') + '<div class="lista-cuerpo"><small>Sede · Turno</small><strong>' + e(nombreDe(DB.sedes, u.sede)) + ' · ' + e(nombreDe(DB.turnos, u.turno)) + '</strong></div></li><li>' + ico('birrete') + '<div class="lista-cuerpo"><small>Semestre actual</small><strong>' + semActual(u.id) + '.º semestre</strong></div></li>' : '') +
      '</ul><p class="muy-tenue" style="margin-top:10px">Para corregir tus datos, comunicate con la Coordinación de Prácticas.</p></section>' + extra +
      '<section class="tarjeta"><h3>Cambiar contraseña</h3><form data-accion="clave"><div class="campo"><label for="a">Contraseña actual</label><input id="a" name="actual" type="password" required></div>' +
      '<div class="campo"><label for="n">Nueva contraseña</label><input id="n" name="nueva" type="password" minlength="8" required><span class="ayuda">Mínimo 8 caracteres.</span></div>' +
      '<div class="campo"><label for="r">Repetir nueva contraseña</label><input id="r" name="repetir" type="password" minlength="8" required></div><button class="btn" type="submit">' + ico('candado') + 'Actualizar contraseña</button></form></section></div>');
  };

  V.estudiantes = function (q) {
    if (!esAdmin()) return ir('#/inicio');
    var f = { q: (q.q || '').toLowerCase(), turno: +q.turno || 0, sem: +q.sem || 0, estado: q.estado || '' };
    var lista = estudiantes().filter(function (s) {
      if (f.q && (s.nombre + ' ' + s.apellido + ' ' + s.email).toLowerCase().indexOf(f.q) < 0) return false;
      if (f.turno && s.turno !== f.turno) return false;
      if (f.sem && semActual(s.id) !== f.sem) return false;
      if (f.estado === 'habilitado' && !habilitado(s.id)) return false;
      if (f.estado === 'no_habilitado' && habilitado(s.id)) return false;
      if (f.estado === 'inactivo' && s.activo) return false;
      return true;
    }).sort(function (a, b) { return (a.apellido + a.nombre).localeCompare(b.apellido + b.nombre); });
    var filas = lista.map(function (s) {
      var pr = prereqDe(s.id);
      return '<tr><td class="td-principal"><strong>' + e(s.apellido + ', ' + s.nombre) + '</strong> ' + (s.activo ? '' : '<span class="etiqueta-estado e-peligro">Desactivado</span>') + '<br><small>' + e(s.email) + '</small></td>' +
        '<td data-et="Semestre">' + semActual(s.id) + '.º</td><td data-et="Turno">' + e(nombreDe(DB.turnos, s.turno)) + '</td><td data-et="Bioseguridad">' + etiquetaSiNo(pr.bio) + '</td><td data-et="DENERS">' + etiquetaSiNo(pr.den) + '</td>' +
        '<td><a class="btn btn-sec btn-chico" href="#/admin/estudiante?id=' + s.id + '">Gestionar ' + ico('flecha_der') + '</a></td></tr>';
    }).join('');
    var sel = function (v, x) { return v === x ? ' selected' : ''; };
    return layout('Estudiantes', 'estudiantes',
      '<details class="plegable" style="margin-bottom:18px"' + (q.nuevo ? ' open' : '') + '><summary>' + ico('mas') + 'Nuevo estudiante</summary><div class="plegable-cuerpo"><form data-accion="est-crear">' +
      '<div class="form-fila"><div class="campo"><label for="n">Nombre</label><input id="n" name="nombre" type="text" required></div><div class="campo"><label for="a">Apellido</label><input id="a" name="apellido" type="text" required></div>' +
      '<div class="campo"><label for="m">Correo (será su usuario)</label><input id="m" name="email" type="email" required></div><div class="campo"><label for="c">Contraseña temporal</label><input id="c" name="clave" type="text" minlength="8" required value="Enf-' + Math.random().toString(16).slice(2, 8) + '"><span class="ayuda">Podrá cambiarla desde su perfil.</span></div></div>' +
      '<div class="form-fila form-fila-3"><div class="campo"><label for="s">Sede</label><select id="s" name="sede">' + opciones(DB.sedes, 1) + '</select></div><div class="campo"><label for="t">Turno</label><select id="t" name="turno">' + opciones(DB.turnos, 1) + '</select></div>' +
      '<div class="campo"><label for="se">Semestre actual</label><select id="se" name="sem">' + opciones(DB.semestres, 1) + '</select><span class="ayuda">Los anteriores quedan como aprobados.</span></div></div>' +
      '<label class="casilla"><input type="checkbox" name="bio" value="1"> Curso de bioseguridad aprobado</label><label class="casilla"><input type="checkbox" name="den" value="1"> Habilitación DENERS vigente</label>' +
      '<button class="btn" type="submit" style="margin-top:8px">' + ico('check') + 'Crear estudiante</button></form></div></details>' +
      '<form class="chip-filtros" data-accion="est-filtrar"><div class="campo"><label class="etiqueta" for="fq">Buscar</label><div class="input-icono">' + ico('buscar') + '<input id="fq" type="search" name="q" value="' + e(q.q || '') + '" placeholder="Nombre o correo"></div></div>' +
      '<div class="campo"><label class="etiqueta" for="fs">Semestre</label><select id="fs" name="sem">' + opciones(DB.semestres, f.sem, 'Todos') + '</select></div>' +
      '<div class="campo"><label class="etiqueta" for="ft">Turno</label><select id="ft" name="turno">' + opciones(DB.turnos, f.turno, 'Todos') + '</select></div>' +
      '<div class="campo"><label class="etiqueta" for="fe">Estado</label><select id="fe" name="estado"><option value="">Todos</option><option value="habilitado"' + sel(f.estado, 'habilitado') + '>Habilitados para prácticas</option><option value="no_habilitado"' + sel(f.estado, 'no_habilitado') + '>Sin habilitación</option><option value="inactivo"' + sel(f.estado, 'inactivo') + '>Cuentas desactivadas</option></select></div>' +
      '<button class="btn btn-suave" type="submit">' + ico('filtro') + 'Filtrar</button></form><p class="muy-tenue">' + lista.length + ' estudiante(s)</p>' +
      '<div class="tabla-envoltura"><table class="tabla-tarjetas"><thead><tr><th>Estudiante</th><th>Semestre</th><th>Turno</th><th>Bioseguridad</th><th>DENERS</th><th></th></tr></thead><tbody>' +
      (filas || '<tr><td colspan="6"><div class="vacio">No hay estudiantes con esos filtros.</div></td></tr>') + '</tbody></table></div>');
  };

  V.estudiante = function (q) {
    if (!esAdmin()) return ir('#/inicio');
    var s = porId(DB.usuarios, q.id);
    if (!s || s.rol !== 'estudiante') { flash('error', 'Estudiante no encontrado.'); return ir('#/admin/estudiantes'); }
    var pr = prereqDe(s.id), av = avanceDe(s.id), hab = habilitado(s.id);
    var cursando = av.filter(function (a) { return a.estado === 'cursando'; });
    var hayAprob = av.some(function (a) { return a.estado === 'aprobado'; });
    var visibles = av.map(function (a) { return a.sem; });
    var procs = DB.procedimientos.filter(function (p) { return p.activo && visibles.indexOf(p.semestre) >= 0; })
      .sort(function (a, b) { return b.semestre - a.semestre || (a.area + a.titulo).localeCompare(b.area + b.titulo); });
    var semA = null, practicas = '';
    procs.forEach(function (p) {
      if (p.semestre !== semA) { semA = p.semestre; practicas += '<h3 style="margin:18px 0 6px">' + semA + '.º semestre</h3>'; }
      var r = practicaDe(s.id, p.id), der;
      if (r) der = '<form data-accion="prac-anular" data-confirmar="¿Anular este registro? Usalo solo si se cargó por error."><input type="hidden" name="id" value="' + r.id + '"><button class="btn btn-peligro btn-chico" type="submit">Anular</button></form>';
      else if (hab) der = '<form data-accion="prac-registrar" class="acciones" style="flex:1 1 320px;justify-content:flex-end"><input type="hidden" name="proc" value="' + p.id + '">' +
        '<input type="date" name="fecha" max="' + hoyISO() + '" value="' + hoyISO() + '" required aria-label="Fecha" style="width:auto;flex:0 0 160px;min-height:38px">' +
        '<input type="text" name="instructor" required placeholder="Instructor que firmó" aria-label="Instructor" style="width:auto;flex:1 1 150px;min-height:38px"><button class="btn btn-chico" type="submit">' + ico('check') + 'Registrar</button></form>';
      else der = '<span class="etiqueta-estado e-neutro">Pendiente</span>';
      practicas += '<div class="procedimiento" style="flex-wrap:wrap"><div class="icono-circulo ' + (r ? 'hecho' : '') + '">' + ico(r ? 'check' : 'pulso') + '</div><div class="lista-cuerpo" style="min-width:180px"><span class="area">' + e(p.area) + '</span><strong>' + e(p.titulo) + '</strong>' +
        (r ? '<small>' + fechaCorta(r.fecha) + ' · firmó ' + e(r.instructor) + '</small>' : '') + '</div>' + der + '</div>';
    });
    return layout(s.nombre + ' ' + s.apellido, 'estudiantes',
      '<a href="#/admin/estudiantes" class="muy-tenue" style="display:inline-flex;gap:4px;align-items:center;margin-bottom:12px">' + ico('flecha_izq') + 'Estudiantes</a>' +
      '<section class="tarjeta" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap"><div class="avatar avatar-grande" style="background:var(--musgo-200)">' + e(iniciales(s.nombre, s.apellido)) + '</div><div style="flex:1;min-width:200px"><h2 style="margin:0">' + e(s.apellido + ', ' + s.nombre) + '</h2>' +
      '<small>' + e(s.email) + ' · ' + e(nombreDe(DB.sedes, s.sede)) + ' · ' + e(nombreDe(DB.turnos, s.turno)) + ' · ' + semActual(s.id) + '.º semestre</small></div>' +
      (hab ? '<span class="etiqueta-estado e-exito">' + ico('check') + 'Habilitado para prácticas</span>' : '<span class="etiqueta-estado e-alerta">' + ico('alerta') + 'Sin habilitación</span>') + (s.activo ? '' : '<span class="etiqueta-estado e-peligro">Cuenta desactivada</span>') + '</section>' +
      '<div class="grilla grilla-2" style="margin-top:18px"><section class="tarjeta" id="requisitos"><h3>Requisitos para prácticas</h3><form data-accion="est-requisitos"><input type="hidden" name="id" value="' + s.id + '">' +
      '<label class="casilla"><input type="checkbox" name="bio" value="1"' + (pr.bio ? ' checked' : '') + '> Curso de bioseguridad aprobado</label><div class="campo"><label for="fb">Fecha de aprobación</label><input id="fb" type="date" name="fbio" value="' + e(pr.fbio || '') + '"></div>' +
      '<label class="casilla"><input type="checkbox" name="den" value="1"' + (pr.den ? ' checked' : '') + '> Habilitación ante la DENERS</label><div class="campo"><label for="fd">Fecha de habilitación</label><input id="fd" type="date" name="fden" value="' + e(pr.fden || '') + '"></div>' +
      '<button class="btn" type="submit">' + ico('check') + 'Guardar y notificar</button></form></section>' +
      '<section class="tarjeta" id="avance"><h3>Avance por semestre</h3><ul class="lista">' + av.map(function (a) {
        var pg = progreso(s.id, a.sem);
        return '<li><div class="icono-circulo ' + (a.estado === 'aprobado' ? 'hecho' : '') + '">' + ico(a.estado === 'aprobado' ? 'check' : 'reloj') + '</div><div class="lista-cuerpo"><strong>' + a.sem + '.º semestre</strong><small>' + (a.estado === 'aprobado' ? 'Aprobado el ' + fechaCorta(a.fecha) : 'En curso') + ' · prácticas ' + pg.hechas + '/' + pg.total + '</small></div></li>';
      }).join('') + '</ul><div class="acciones" style="margin-top:12px">' +
      (cursando.length ? '<form data-accion="est-aprobar" data-confirmar="¿Confirmar que aprobó el ' + cursando[0].sem + '.º semestre? Se habilitará el siguiente y se le avisará."><input type="hidden" name="id" value="' + s.id + '"><button class="btn" type="submit">' + ico('birrete') + 'Aprobar ' + cursando[0].sem + '.º semestre</button></form>' : '') +
      (hayAprob ? '<form data-accion="est-deshacer" data-confirmar="¿Deshacer la última aprobación? Se ocultará el semestre siguiente."><input type="hidden" name="id" value="' + s.id + '"><button class="btn btn-suave btn-chico" type="submit">Deshacer última aprobación</button></form>' : '') +
      '</div></section></div>' +
      '<section class="tarjeta" id="practicas" style="margin-top:18px"><div class="tarjeta-titulo"><h3>Prácticas firmadas en la libreta física</h3></div><p class="muy-tenue">Registrá aquí cada práctica que el instructor firmó en papel. El estudiante la verá marcada en su libreta y recibirá el aviso.</p>' +
      (hab ? '' : '<div class="aviso aviso-alerta">' + ico('alerta') + '<span>Para registrar prácticas, el estudiante debe tener aprobados la bioseguridad y la DENERS.</span></div>') +
      '<input type="hidden" id="est-actual" value="' + s.id + '">' + (practicas || '<div class="vacio">No hay procedimientos cargados para sus semestres.</div>') + '</section>' +
      '<details class="plegable" style="margin-top:18px"><summary>' + ico('editar') + 'Editar datos de la cuenta</summary><div class="plegable-cuerpo"><form data-accion="est-datos"><input type="hidden" name="id" value="' + s.id + '"><div class="form-fila">' +
      '<div class="campo"><label for="en">Nombre</label><input id="en" name="nombre" type="text" value="' + e(s.nombre) + '" required></div><div class="campo"><label for="ea">Apellido</label><input id="ea" name="apellido" type="text" value="' + e(s.apellido) + '" required></div>' +
      '<div class="campo"><label for="ee">Correo</label><input id="ee" name="email" type="email" value="' + e(s.email) + '" required></div><div class="campo"><label for="ec">Nueva contraseña <small>(opcional)</small></label><input id="ec" name="clave" type="text" minlength="8" placeholder="Dejar vacío para no cambiar"></div>' +
      '<div class="campo"><label for="es">Sede</label><select id="es" name="sede">' + opciones(DB.sedes, s.sede) + '</select></div><div class="campo"><label for="et">Turno</label><select id="et" name="turno">' + opciones(DB.turnos, s.turno) + '</select></div></div>' +
      '<label class="casilla"><input type="checkbox" name="activo" value="1"' + (s.activo ? ' checked' : '') + '> Cuenta activa (si se desactiva, no puede ingresar)</label><button class="btn" type="submit" style="margin-top:8px">' + ico('check') + 'Guardar datos</button></form></div></details>',
      { volver: '#/admin/estudiantes' });
  };

  V.procedimientos = function (q) {
    if (!esAdmin()) return ir('#/inicio');
    var sem = +q.sem || 1;
    var ed = q.editar ? porId(DB.procedimientos, q.editar) : null;
    var v = ed || { id: 0, semestre: sem, area: '', titulo: '', descripcion: '', pasos: [], activo: true };
    var tabs = DB.semestres.map(function (s) {
      var c = DB.procedimientos.filter(function (p) { return p.activo && p.semestre === s.id; }).length;
      return '<a class="pestana ' + (s.id === sem ? 'activa' : '') + '" href="#/admin/procedimientos?sem=' + s.id + '">' + e(s.nombre) + ' <small>(' + c + ')</small></a>';
    }).join('');
    var lista = DB.procedimientos.filter(function (p) { return p.semestre === sem; }).sort(function (a, b) { return (a.area + a.titulo).localeCompare(b.area + b.titulo); }).map(function (p) {
      var regs = DB.practicas.filter(function (r) { return r.proc === p.id; }).length;
      return '<div class="procedimiento" style="' + (p.activo ? '' : 'opacity:.6') + '"><div class="icono-circulo">' + ico('pulso') + '</div><a class="lista-cuerpo" href="#/procedimiento?id=' + p.id + '" style="color:inherit;text-decoration:none"><span class="area">' + e(p.area) + '</span><strong>' + e(p.titulo) + '</strong><small>' + p.pasos.length + ' pasos · ' + regs + ' práctica(s) registrada(s)' + (p.activo ? '' : ' · <b>oculto</b>') + '</small></a>' +
        '<a class="btn-icono" href="#/admin/procedimientos?sem=' + sem + '&editar=' + p.id + '" aria-label="Editar">' + ico('editar') + '</a></div>';
    }).join('');
    return layout('Libreta · Procedimientos', 'procedimientos', '<p class="tenue">La libreta es de consulta: aquí se cargan los procedimientos que cada estudiante verá según su semestre habilitado.</p><nav class="pestanas">' + tabs + '</nav>' +
      '<div class="grilla grilla-principal"><section>' + (lista || '<div class="tarjeta vacio">' + ico('libreta') + 'Este semestre todavía no tiene procedimientos.</div>') + '</section>' +
      '<section class="tarjeta" id="form"><h3>' + (ed ? 'Editar procedimiento' : 'Nuevo procedimiento') + '</h3><form data-accion="proc-guardar"><input type="hidden" name="id" value="' + v.id + '"><div class="form-fila">' +
      '<div class="campo"><label for="ps">Semestre</label><select id="ps" name="sem">' + opciones(DB.semestres, v.semestre) + '</select></div><div class="campo"><label for="pa">Área de atención</label><input id="pa" name="area" type="text" required value="' + e(v.area) + '" placeholder="Ej.: Fundamentos"></div></div>' +
      '<div class="campo"><label for="pt">Título</label><input id="pt" name="titulo" type="text" required value="' + e(v.titulo) + '"></div><div class="campo"><label for="pd">Descripción</label><textarea id="pd" name="descripcion" rows="2">' + e(v.descripcion || '') + '</textarea></div>' +
      '<div class="campo"><label for="pp">Pasos</label><textarea id="pp" name="pasos" rows="7" placeholder="Un paso por línea">' + e(v.pasos.join('\n')) + '</textarea><span class="ayuda">Escribí un paso por línea; se numeran solos.</span></div>' +
      '<label class="casilla"><input type="checkbox" name="activo" value="1"' + (v.activo ? ' checked' : '') + '> Visible en la libreta</label><div class="acciones" style="margin-top:8px"><button class="btn" type="submit">' + ico('check') + 'Guardar</button>' +
      (ed ? '<a class="btn btn-suave" href="#/admin/procedimientos?sem=' + sem + '">Cancelar</a>' : '') + '</div></form></section></div>');
  };

  // ------------------------------------------------------------------
  // Acciones de formularios
  // ------------------------------------------------------------------
  var A = {};
  A.login = function (d) {
    if (DB.intentos >= 5 && Date.now() - (DB.ultimoIntento || 0) < 60000) { flash('error', 'Demasiados intentos. Esperá un minuto y volvé a probar.'); return render(); }
    var u = DB.usuarios.filter(function (x) { return x.activo && x.email.toLowerCase() === (d.email || '').trim().toLowerCase(); })[0];
    if (u && u.clave === d.clave) { DB.sesion = u.id; DB.intentos = 0; guardar(); return ir('#/inicio'); }
    DB.intentos++; DB.ultimoIntento = Date.now(); guardar();
    flash('error', 'Correo o contraseña incorrectos.'); render();
  };
  A['evento-guardar'] = function (d) {
    var id = +d.id;
    if (!d.titulo.trim() || !d.inicio) { flash('error', 'Completá el título, el tipo y la fecha de inicio.'); return render(); }
    if (d.fin && d.fin < d.inicio) { flash('error', 'La fecha de fin no puede ser anterior al inicio.'); return render(); }
    var v = { titulo: d.titulo.trim(), tipo: d.tipo, descripcion: d.descripcion.trim(), inicio: d.inicio, fin: d.fin || null, sede: +d.sede || null, turno: +d.turno || null, sem: +d.sem || null };
    var dest = destinatarios(v.sede, v.turno, v.sem), msg;
    if (id) {
      var ant = porId(DB.eventos, id), cambios = [];
      dest = dest.concat(destinatarios(ant.sede, ant.turno, ant.sem));
      if (ant.inicio !== v.inicio) cambios.push('Nueva fecha: ' + fechaLarga(v.inicio, true) + ' (antes: ' + fechaLarga(ant.inicio, true) + ').');
      if (ant.titulo !== v.titulo) cambios.push('Antes se llamaba: «' + ant.titulo + '».');
      if ((ant.descripcion || '') !== v.descripcion) cambios.push('Se actualizó la descripción.');
      Object.keys(v).forEach(function (k) { ant[k] = v[k]; });
      msg = notificar(dest, 'Cambio en el calendario', TIPOS[v.tipo] + ': «' + v.titulo + '». ' + (cambios.length ? cambios.join(' ') : 'Se modificaron los datos del evento.'), '#/calendario?m=' + v.inicio.slice(0, 7));
      flash('exito', 'Evento actualizado.' + msg);
    } else {
      v.id = nuevoId(DB.eventos); DB.eventos.push(v);
      msg = d.notificar ? notificar(dest, 'Nueva fecha en el calendario', TIPOS[v.tipo] + ': «' + v.titulo + '», ' + fechaLarga(v.inicio, true) + '.', '#/calendario?m=' + v.inicio.slice(0, 7)) : ' Sin notificar.';
      flash('exito', 'Evento creado.' + msg);
    }
    guardar(); ir('#/calendario?m=' + v.inicio.slice(0, 7));
  };
  A['evento-eliminar'] = function (d) {
    var ev = porId(DB.eventos, d.id); if (!ev) return render();
    DB.eventos = DB.eventos.filter(function (x) { return x.id !== ev.id; });
    var msg = notificar(destinatarios(ev.sede, ev.turno, ev.sem), 'Evento cancelado', TIPOS[ev.tipo] + ': «' + ev.titulo + '» del ' + fechaLarga(ev.inicio, true) + ' fue cancelado.', '#/calendario');
    guardar(); flash('exito', 'Evento eliminado.' + msg); ir('#/calendario');
  };
  A['doc-buscar'] = function (d) {
    ir('#/documentos?' + (d.cat ? 'cat=' + encodeURIComponent(d.cat) + '&' : '') + 'q=' + encodeURIComponent(d.q || ''));
  };
  A['doc-guardar'] = function (d, form) {
    if (!d.titulo.trim() || !CATS[d.categoria]) { flash('error', 'Completá el título y la categoría.'); return render(); }
    var file = form.querySelector('input[type=file]').files[0];
    var terminar = function (dataUrl) {
      var id = +d.id, doc = id ? porId(DB.documentos, id) : { id: nuevoId(DB.documentos), archivo: null, nombre: null };
      doc.titulo = d.titulo.trim(); doc.categoria = d.categoria; doc.area = d.area.trim() || null; doc.descripcion = d.descripcion.trim() || null;
      doc.version = d.version.trim() || '1.0'; doc.publicado = !!d.publicado; doc.fecha = hoyISO();
      if (dataUrl) { doc.archivo = dataUrl; doc.nombre = file.name; }
      if (!id) DB.documentos.push(doc);
      var msg = '';
      if (dataUrl && d.notificar) msg = notificar(estudiantes().filter(function (s) { return s.activo; }).map(function (s) { return s.id; }), 'Documento actualizado', '«' + doc.titulo + '» (versión ' + doc.version + ') ya está disponible para consulta.', '#/documentos?cat=' + doc.categoria);
      if (!guardar()) { doc.archivo = null; flash('error', 'No se pudo guardar ese PDF. Probá con uno más liviano.'); }
      else flash('exito', 'Documento guardado.' + msg);
      ir('#/documentos?cat=' + doc.categoria);
    };
    if (!file) return terminar(null);
    if (file.size > 2 * 1024 * 1024) { flash('error', 'El PDF puede pesar hasta 2 MB.'); return render(); }
    var lector = new FileReader();
    lector.onload = function () {
      if (String(lector.result).indexOf('data:application/pdf') !== 0) { flash('error', 'El archivo debe ser un PDF.'); return render(); }
      terminar(lector.result);
    };
    lector.readAsDataURL(file);
  };
  A['doc-eliminar'] = function (d) {
    DB.documentos = DB.documentos.filter(function (x) { return x.id !== +d.id; });
    guardar(); flash('exito', 'Documento eliminado.'); ir('#/documentos');
  };
  A['leer-todas'] = function () {
    var u = yo(); DB.notificaciones.forEach(function (n) { if (n.usuario === u.id) n.leida = true; });
    guardar(); render();
  };
  A.clave = function (d) {
    var u = yo();
    if (d.actual !== u.clave) flash('error', 'La contraseña actual no es correcta.');
    else if ((d.nueva || '').length < 8 || d.nueva !== d.repetir) flash('error', 'La nueva contraseña debe tener al menos 8 caracteres y coincidir en ambos campos.');
    else { u.clave = d.nueva; guardar(); flash('exito', 'Contraseña actualizada.'); }
    render();
  };
  A['est-filtrar'] = function (d) {
    var p = []; ['q', 'sem', 'turno', 'estado'].forEach(function (k) { if (d[k]) p.push(k + '=' + encodeURIComponent(d[k])); });
    ir('#/admin/estudiantes' + (p.length ? '?' + p.join('&') : ''));
  };
  A['est-crear'] = function (d) {
    var err = [];
    if (!d.nombre.trim() || !d.apellido.trim()) err.push('nombre y apellido');
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(d.email.trim())) err.push('un correo válido');
    if ((d.clave || '').length < 8) err.push('una contraseña temporal de 8 caracteres o más');
    if (DB.usuarios.some(function (u) { return u.email.toLowerCase() === d.email.trim().toLowerCase(); })) err.push('un correo que no esté registrado');
    if (err.length) { flash('error', 'Falta: ' + err.join(', ') + '.'); return ir('#/admin/estudiantes?nuevo=1'); }
    var id = nuevoId(DB.usuarios), sem = +d.sem || 1;
    DB.usuarios.push({ id: id, nombre: d.nombre.trim(), apellido: d.apellido.trim(), email: d.email.trim().toLowerCase(), clave: d.clave, rol: 'estudiante', sede: +d.sede, turno: +d.turno, activo: true });
    for (var n = 1; n <= sem; n++) DB.avance.push({ id: nuevoId(DB.avance), est: id, sem: n, estado: n < sem ? 'aprobado' : 'cursando', fecha: n < sem ? hoyISO() : null });
    DB.prereq.push({ est: id, bio: !!d.bio, fbio: d.bio ? hoyISO() : null, den: !!d.den, fden: d.den ? hoyISO() : null });
    notificar([id], 'Bienvenida a la plataforma', 'Tu cuenta fue creada. Ya podés consultar tu Libreta Digital de Prácticas, el calendario y los documentos de la carrera.', '#/inicio');
    guardar(); flash('exito', 'Estudiante creado. Entregale su usuario (' + d.email.trim().toLowerCase() + ') y la contraseña temporal.');
    ir('#/admin/estudiante?id=' + id);
  };
  A['est-requisitos'] = function (d) {
    var id = +d.id, p = prereqDe(id);
    p.bio = !!d.bio; p.fbio = p.bio ? (d.fbio || hoyISO()) : null; p.den = !!d.den; p.fden = p.den ? (d.fden || hoyISO()) : null;
    var falta = []; if (!p.bio) falta.push('curso de bioseguridad'); if (!p.den) falta.push('habilitación DENERS');
    var msg = notificar([id], 'Actualización de tus requisitos', falta.length ? 'Pendiente: ' + falta.join(' y ') + '.' : 'Ya estás habilitado/a para realizar prácticas.', '#/perfil');
    guardar(); flash('exito', 'Requisitos guardados.' + msg); render();
  };
  A['est-aprobar'] = function (d) {
    var id = +d.id, cur = avanceDe(id).filter(function (a) { return a.estado === 'cursando'; }).pop();
    if (!cur) return render();
    cur.estado = 'aprobado'; cur.fecha = hoyISO();
    var sig = cur.sem + 1 <= 8 ? cur.sem + 1 : null;
    if (sig && !avanceDe(id).some(function (a) { return a.sem === sig; })) DB.avance.push({ id: nuevoId(DB.avance), est: id, sem: sig, estado: 'cursando', fecha: null });
    var msg = notificar([id], 'Semestre aprobado', 'Aprobaste el ' + cur.sem + '.º semestre.' + (sig ? ' Ya podés ver los procedimientos del ' + sig + '.º semestre en tu libreta.' : ' ¡Completaste todos los semestres!'), '#/libreta');
    guardar(); flash('exito', cur.sem + '.º semestre aprobado' + (sig ? ' y ' + sig + '.º habilitado' : '') + '.' + msg); render();
  };
  A['est-deshacer'] = function (d) {
    var id = +d.id, ult = avanceDe(id).filter(function (a) { return a.estado === 'aprobado'; }).pop();
    if (!ult) return render();
    DB.avance = DB.avance.filter(function (a) { return !(a.est === id && a.sem > ult.sem); });
    ult.estado = 'cursando'; ult.fecha = null;
    guardar(); flash('exito', 'Se deshizo la última aprobación.'); render();
  };
  A['prac-registrar'] = function (d) {
    var id = +document.getElementById('est-actual').value, p = porId(DB.procedimientos, d.proc);
    if (!habilitado(id)) flash('error', 'No se puede registrar: el estudiante no está habilitado para prácticas.');
    else if (!p || !d.fecha || !d.instructor.trim() || d.fecha > hoyISO()) flash('error', 'Indicá la fecha (no futura) y el instructor que firmó la libreta.');
    else if (!practicaDe(id, p.id)) {
      DB.practicas.push({ id: nuevoId(DB.practicas), est: id, proc: p.id, fecha: d.fecha, instructor: d.instructor.trim(), registro: hoyISO() });
      var msg = notificar([id], 'Práctica registrada', 'Se registró la práctica «' + p.titulo + '» del ' + fechaCorta(d.fecha) + ', firmada por ' + d.instructor.trim() + '.', '#/procedimiento?id=' + p.id);
      guardar(); flash('exito', 'Práctica registrada.' + msg);
    }
    render();
  };
  A['prac-anular'] = function (d) {
    DB.practicas = DB.practicas.filter(function (r) { return r.id !== +d.id; });
    guardar(); flash('exito', 'Registro anulado.'); render();
  };
  A['est-datos'] = function (d) {
    var s = porId(DB.usuarios, d.id), email = d.email.trim().toLowerCase();
    var dup = DB.usuarios.some(function (u) { return u.id !== s.id && u.email.toLowerCase() === email; });
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) || dup || !d.nombre.trim()) { flash('error', 'Revisá el nombre y que el correo sea válido y no esté repetido.'); return render(); }
    s.nombre = d.nombre.trim(); s.apellido = d.apellido.trim(); s.email = email; s.sede = +d.sede; s.turno = +d.turno; s.activo = !!d.activo;
    if (d.clave) { if (d.clave.length < 8) flash('error', 'La contraseña debe tener al menos 8 caracteres; no se cambió.'); else s.clave = d.clave; }
    guardar(); flash('exito', 'Datos actualizados.'); render();
  };
  A['proc-guardar'] = function (d) {
    if (!d.area.trim() || !d.titulo.trim()) { flash('error', 'Completá el área y el título.'); return render(); }
    var id = +d.id, p = id ? porId(DB.procedimientos, id) : { id: nuevoId(DB.procedimientos) };
    p.semestre = +d.sem; p.area = d.area.trim(); p.titulo = d.titulo.trim(); p.descripcion = d.descripcion.trim();
    p.pasos = d.pasos.split('\n').map(function (x) { return x.trim(); }).filter(Boolean); p.activo = !!d.activo;
    if (!id) DB.procedimientos.push(p);
    guardar(); flash('exito', id ? 'Procedimiento actualizado.' : 'Procedimiento agregado a la libreta.'); ir('#/admin/procedimientos?sem=' + p.semestre);
  };

  // ------------------------------------------------------------------
  // Render y eventos
  // ------------------------------------------------------------------
  var RUTAS = {
    '/inicio': V.inicio, '/libreta': V.libreta, '/procedimiento': V.procedimiento, '/calendario': V.calendario,
    '/documentos': V.documentos, '/notificaciones': V.notificaciones, '/perfil': V.perfil,
    '/admin/estudiantes': V.estudiantes, '/admin/estudiante': V.estudiante, '/admin/procedimientos': V.procedimientos
  };
  function render() {
    var r = ruta(), app = document.getElementById('app'), html;
    if (DB.sesion && !yo()) DB.sesion = null;
    if (!yo()) html = V.login();
    else html = (RUTAS[r.path] || V.inicio)(r.q);
    if (typeof html !== 'string') return;
    app.innerHTML = html;
    window.scrollTo(0, 0);
    if (r.q.editar) { var f = document.getElementById('form'); if (f) f.scrollIntoView(); }
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target, accion = form.getAttribute('data-accion');
    if (!accion || !A[accion]) return;
    ev.preventDefault();
    var c = form.getAttribute('data-confirmar');
    if (c && !window.confirm(c)) return;
    var d = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.type === 'file') return;
      if (el.type === 'checkbox') { if (el.checked) d[el.name] = el.value; }
      else d[el.name] = el.value;
    });
    ['titulo', 'descripcion', 'area', 'version', 'nombre', 'apellido', 'email', 'pasos', 'instructor', 'q'].forEach(function (k) { if (d[k] == null) d[k] = ''; });
    A[accion](d, form);
  });

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('button, a');
    if (!t) return;
    if (t.hasAttribute('data-tema-btn')) {
      var raiz = document.documentElement, nuevo = raiz.getAttribute('data-tema') === 'oscuro' ? 'claro' : 'oscuro';
      raiz.setAttribute('data-tema', nuevo); try { localStorage.setItem('tema', nuevo); } catch (x) {}
    } else if (t.hasAttribute('data-salir')) {
      DB.sesion = null; guardar(); ir('#/inicio');
    } else if (t.hasAttribute('data-reiniciar')) {
      if (window.confirm('¿Volver a los datos de ejemplo? Se borran los cambios hechos en este navegador.')) {
        var ses = DB.sesion; DB = datosIniciales(); DB.sesion = ses; guardar(); flash('exito', 'Datos de ejemplo restaurados.'); render();
      }
    } else if (t.hasAttribute('data-cuenta')) {
      var c = t.getAttribute('data-cuenta').split('|');
      document.getElementById('email').value = c[0]; document.getElementById('clave').value = c[1];
      document.getElementById('clave').focus();
    } else if (t.hasAttribute('data-abrir-doc')) {
      var doc = porId(DB.documentos, t.getAttribute('data-abrir-doc'));
      if (doc && doc.archivo) {
        var bytes = atob(doc.archivo.split(',')[1]), arr = new Uint8Array(bytes.length);
        for (var i = 0; i < bytes.length; i++) arr[i] = bytes.charCodeAt(i);
        window.open(URL.createObjectURL(new Blob([arr], { type: 'application/pdf' })), '_blank');
      }
    } else if (t.hasAttribute('data-abrir-notif')) {
      var n = porId(DB.notificaciones, t.getAttribute('data-abrir-notif'));
      if (n) { n.leida = true; guardar(); ir(n.enlace || '#/notificaciones'); }
    }
  });

  window.addEventListener('hashchange', render);
  render();
})();
