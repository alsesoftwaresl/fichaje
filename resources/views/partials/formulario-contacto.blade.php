{{--
    Formulario de contacto (lo usan la portada y /contacto).
    Variables: $origen ('home' o 'contacto', a dónde se vuelve tras enviar) y
    $asuntoInicial (motivo preseleccionado, opcional).
--}}
@php($asuntos = \App\Models\Contacto::ASUNTOS)

@if (session('enviado'))
    <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
        <p class="font-semibold">Mensaje enviado.</p>
        <p class="mt-1 text-sm">Gracias por escribirnos. Te responderemos por correo electrónico.</p>
    </div>
@else
    <form method="POST" action="{{ route('contacto.store') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="origen" value="{{ $origen ?? 'contacto' }}">

        {{-- Trampa para bots: las personas no ven este campo. --}}
        <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
            <label for="web">No rellenar</label>
            <input id="web" name="web" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="nombre" class="block text-sm font-medium text-slate-700">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required maxlength="120" autocomplete="name"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('nombre')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="empresa" class="block text-sm font-medium text-slate-700">Empresa <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="empresa" name="empresa" type="text" value="{{ old('empresa') }}" maxlength="160" autocomplete="organization"
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('empresa')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="asunto" class="block text-sm font-medium text-slate-700">Motivo</label>
                <select id="asunto" name="asunto" required
                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($asuntos as $clave => $texto)
                        <option value="{{ $clave }}" @selected(old('asunto', $asuntoInicial ?? 'informacion') === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
                @error('asunto')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="mensaje" class="block text-sm font-medium text-slate-700">Mensaje</label>
            <textarea id="mensaje" name="mensaje" rows="5" required maxlength="4000"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('mensaje') }}</textarea>
            @error('mensaje')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="flex items-start gap-3 text-sm text-slate-600">
                <input type="checkbox" name="acepta_privacidad" value="1" @checked(old('acepta_privacidad')) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>
                    Acepto que {{ config('legal.titular.nombre') }} trate mis datos para responder a este mensaje, según la
                    <a href="{{ route('legal.privacidad') }}" class="text-indigo-600 hover:underline">política de privacidad</a>.
                </span>
            </label>
            @error('acepta_privacidad')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-indigo-950 px-6 py-3 font-semibold text-white hover:bg-indigo-900 transition">
            Enviar mensaje
        </button>
    </form>
@endif
