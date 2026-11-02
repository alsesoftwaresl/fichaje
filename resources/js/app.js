import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ---- Aplicación instalable (PWA) -------------------------------------------------
// El navegador avisa una sola vez de que se puede instalar; se guarda el aviso para
// poder lanzarlo cuando la persona pulse "Instalar app".
window.addEventListener('beforeinstallprompt', (evento) => {
    evento.preventDefault();
    window.pwaEvento = evento;
    window.dispatchEvent(new CustomEvent('pwa-lista'));
});

window.addEventListener('appinstalled', () => {
    window.pwaEvento = null;
    window.dispatchEvent(new CustomEvent('pwa-instalada'));
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

const DIAS_SIN_VOLVER_A_PREGUNTAR = 14;

Alpine.data('instalarApp', () => ({
    visible: false,
    ayuda: false,
    ios: false,

    init() {
        const ua = navigator.userAgent;
        this.ios = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        window.addEventListener('pwa-lista', () => this.revisar());
        window.addEventListener('pwa-instalada', () => { this.visible = false; });
        this.revisar();
    },

    ya_instalada() {
        return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    },

    descartada() {
        try {
            const cuando = Number(localStorage.getItem('pwa-descartada') || 0);
            return Date.now() - cuando < DIAS_SIN_VOLVER_A_PREGUNTAR * 86400000;
        } catch (e) {
            return false;
        }
    },

    revisar() {
        this.visible = !this.ya_instalada() && !this.descartada() && (!!window.pwaEvento || this.ios);
    },

    async instalar() {
        if (window.pwaEvento) {
            window.pwaEvento.prompt();
            await window.pwaEvento.userChoice;
            window.pwaEvento = null;
            this.visible = false;
        } else if (this.ios) {
            this.ayuda = !this.ayuda;
        }
    },

    descartar() {
        try {
            localStorage.setItem('pwa-descartada', String(Date.now()));
        } catch (e) { /* sin almacenamiento: simplemente se oculta ahora */ }
        this.visible = false;
    },
}));

// ---- Código QR de la verificación en dos pasos (perfil) -------------------------
// La librería se descarga solo en esa pantalla (import dinámico: va en un archivo
// aparte y no pesa en el resto de la app). Se dibuja como SVG, sin servicios externos.
Alpine.data('codigoQr', (texto) => ({
    async init() {
        const { default: qrcode } = await import('qrcode-generator');
        const qr = qrcode(0, 'M');
        qr.addData(texto);
        qr.make();
        this.$el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    },
}));

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
