// Panel (AdminLTE): "Cerrar sesión" del menú izquierdo.
// Laravel exige POST con token CSRF para salir, así que se arma y envía un formulario.
document.addEventListener('click', function (evento) {
    var enlace = evento.target.closest('#menu-cerrar-sesion a');
    if (!enlace) return;

    evento.preventDefault();

    var token = document.querySelector('meta[name="csrf-token"]');
    var formulario = document.createElement('form');
    formulario.method = 'POST';
    formulario.action = window.location.origin + '/admin/logout';
    formulario.innerHTML = '<input type="hidden" name="_token" value="' + (token ? token.content : '') + '">';
    document.body.appendChild(formulario);
    formulario.submit();
});
