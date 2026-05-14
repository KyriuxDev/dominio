function filtrarTabla(inputId, tablaId, contadorId) {
    var texto = document.getElementById(inputId).value.toLowerCase();
    var filas = document.querySelectorAll('#' + tablaId + ' tbody tr');
    var visible = 0;
    for (var i = 0; i < filas.length; i++) {
        if (filas[i].textContent.toLowerCase().indexOf(texto) !== -1) {
            filas[i].style.display = '';
            visible++;
        } else {
            filas[i].style.display = 'none';
        }
    }
    if (document.getElementById(contadorId)) {
        document.getElementById(contadorId).innerHTML =
            '<strong>' + visible + '</strong> usuario(s)';
    }
}