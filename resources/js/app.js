import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Etiqueta cada celda con el texto de su cabecera para las tablas que en móvil
// se apilan como tarjetas (ver .tabla-apilada en app.css).
document.querySelectorAll('table.tabla-apilada').forEach((tabla) => {
    const cabeceras = [...tabla.querySelectorAll('thead th')].map((th) => th.textContent.trim());

    tabla.querySelectorAll('tbody tr').forEach((fila) => {
        [...fila.children].forEach((celda, i) => {
            if (celda.hasAttribute('colspan')) {
                return;
            }
            celda.setAttribute('data-label', cabeceras[i] ?? '');
        });
    });
});
