// Alternar modo claro / oscuro y recordarlo en este navegador
document.querySelectorAll('[data-alternar-tema]').forEach(function (boton) {
  boton.addEventListener('click', function () {
    var raiz = document.documentElement;
    var nuevo = raiz.getAttribute('data-tema') === 'oscuro' ? 'claro' : 'oscuro';
    raiz.setAttribute('data-tema', nuevo);
    try { localStorage.setItem('tema', nuevo); } catch (e) {}
  });
});

// Confirmación para acciones importantes (aprobar semestre, eliminar, etc.)
document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
  form.addEventListener('submit', function (ev) {
    if (!window.confirm(form.getAttribute('data-confirmar'))) ev.preventDefault();
  });
});

// Evita doble envío de formularios
document.querySelectorAll('form').forEach(function (form) {
  form.addEventListener('submit', function (ev) {
    if (ev.defaultPrevented) return;
    setTimeout(function () {
      form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
    }, 0);
  });
});
