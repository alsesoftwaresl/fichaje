<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Kiosco — {{ $empresa->nombre }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-900 text-white h-screen overflow-hidden select-none">

    @if (session('resultado'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => { window.location.href = window.location.pathname }, 3000)"
            x-show="show"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900"
        >
            @php $resultado = session('resultado'); @endphp
            <div class="flex h-20 w-20 items-center justify-center rounded-full {{ $resultado['tipo'] === 'entrada' ? 'bg-emerald-500' : 'bg-rose-500' }} mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-10 h-10">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <p class="text-2xl font-semibold">Hola, {{ $resultado['nombre'] }}</p>
            <p class="mt-2 text-lg text-slate-300">
                {{ $resultado['tipo'] === 'entrada' ? 'Entrada registrada' : 'Salida registrada' }}
            </p>
            <p class="mt-1 text-sm text-slate-500">{{ now()->format('H:i:s') }}</p>
        </div>
    @endif

    <div class="h-full flex flex-col items-center justify-center px-4"
         x-data="{
            pin: '',
            add(n) { if (this.pin.length < 6) { this.pin += n } },
            back() { this.pin = this.pin.slice(0, -1) },
            clear() { this.pin = '' },
         }"
         x-init="$watch('pin', value => { if (value.length === 6) { $nextTick(() => $refs.form.submit()) } })"
    >
        <p class="text-sm text-slate-400 mb-1">{{ $empresa->nombre }}</p>
        <h1 class="text-xl font-semibold mb-8">Introduce tu PIN para fichar</h1>

        @error('pin')
            <p class="mb-4 text-sm text-rose-400">{{ $message }}</p>
        @enderror

        <!-- Indicador de dígitos -->
        <div class="flex gap-3 mb-10">
            <template x-for="i in 6" :key="i">
                <span class="h-4 w-4 rounded-full border-2 border-slate-500"
                      :class="pin.length >= i ? 'bg-indigo-500 border-indigo-500' : ''"></span>
            </template>
        </div>

        <!-- Teclado -->
        <div class="grid grid-cols-3 gap-4 w-full max-w-xs">
            @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                <button type="button" @click="add('{{ $n }}')"
                    class="h-16 rounded-xl bg-slate-800 hover:bg-slate-700 text-2xl font-semibold transition">
                    {{ $n }}
                </button>
            @endforeach
            <button type="button" @click="clear()" class="h-16 rounded-xl bg-slate-800 hover:bg-slate-700 text-sm font-medium transition">
                Borrar
            </button>
            <button type="button" @click="add('0')" class="h-16 rounded-xl bg-slate-800 hover:bg-slate-700 text-2xl font-semibold transition">
                0
            </button>
            <button type="button" @click="back()" class="h-16 rounded-xl bg-slate-800 hover:bg-slate-700 flex items-center justify-center transition">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l-7-7 7-7m-7 7h18" />
                </svg>
            </button>
        </div>

        <form x-ref="form" method="POST" action="{{ route('kiosko.fichar', $empresa->kiosko_token) }}">
            @csrf
            <input type="hidden" name="pin" :value="pin">
        </form>
    </div>
</body>
</html>
