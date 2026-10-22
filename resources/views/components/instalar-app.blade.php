{{--
    Banner "Instala Achrono en tu móvil". Solo aparece si el navegador permite instalar la web
    (o en iPhone, con las instrucciones), si la app no está ya instalada y si no se ha
    descartado hace poco. La lógica está en resources/js/app.js (instalarApp).
--}}
<div x-data="instalarApp" x-show="visible" x-cloak
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
     class="fixed inset-x-3 bottom-3 z-50 sm:inset-x-auto sm:bottom-4 sm:right-4 sm:w-96"
     style="padding-bottom: env(safe-area-inset-bottom)"
     role="dialog" aria-label="Instalar la aplicación">
    <div class="rounded-2xl bg-indigo-950 p-4 text-white shadow-2xl shadow-black/30 ring-1 ring-white/10">
        <div class="flex items-start gap-3">
            <img src="{{ asset('images/icon-192.png') }}" alt="" width="48" height="48" class="h-12 w-12 shrink-0 rounded-xl">
            <div class="min-w-0 flex-1">
                <p class="font-semibold">Instala {{ config('app.name') }} en tu móvil</p>
                <p class="mt-0.5 text-sm text-indigo-200">Ábrela con un toque desde la pantalla de inicio, a pantalla completa y sin la barra del navegador.</p>
            </div>
            <button type="button" @click="descartar()" aria-label="Cerrar" class="-mr-1 -mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-indigo-300 hover:bg-white/10 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="button" @click="instalar()" class="flex-1 rounded-lg bg-accent-500 px-4 py-2.5 text-sm font-semibold text-indigo-950 transition hover:bg-accent-400">
                Instalar app
            </button>
            <button type="button" @click="descartar()" class="rounded-lg px-3 py-2.5 text-sm font-medium text-indigo-200 hover:text-white">Ahora no</button>
        </div>

        {{-- iPhone/iPad: no hay botón de instalar del navegador, se explica el gesto --}}
        <div x-show="ayuda" x-cloak class="mt-3 rounded-lg bg-white/10 p-3 text-sm text-indigo-100">
            En el iPhone: pulsa el botón <strong>Compartir</strong>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="mx-0.5 inline h-4 w-4 align-text-bottom" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" /></svg>
            y después <strong>«Añadir a pantalla de inicio»</strong>.
        </div>
    </div>
</div>
