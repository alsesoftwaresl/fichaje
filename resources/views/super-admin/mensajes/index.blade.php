<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Mensajes de contacto</h2>
    </x-slot>

    <div class="space-y-4">
        @forelse ($mensajes as $mensaje)
            <x-card class="p-5 {{ $mensaje->leido_en ? 'opacity-70' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-900">{{ $mensaje->nombre }}</p>
                            <x-badge :tone="$mensaje->esUrgente() ? 'danger' : 'neutral'">{{ $mensaje->asuntoTexto() }}</x-badge>
                            @unless ($mensaje->leido_en)
                                <x-badge tone="indigo">Nuevo</x-badge>
                            @endunless
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            <a href="mailto:{{ $mensaje->email }}?subject={{ rawurlencode('Re: '.$mensaje->asuntoTexto()) }}" class="text-indigo-600 hover:underline">{{ $mensaje->email }}</a>
                            @if ($mensaje->empresa) · {{ $mensaje->empresa }} @endif
                            · {{ $mensaje->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('super-admin.mensajes.toggle', $mensaje) }}">
                        @csrf
                        @method('PATCH')
                        <button class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                            {{ $mensaje->leido_en ? 'Marcar como no leído' : 'Marcar como leído' }}
                        </button>
                    </form>
                </div>
                <p class="mt-3 whitespace-pre-line text-sm text-slate-700">{{ $mensaje->mensaje }}</p>
            </x-card>
        @empty
            <x-card class="p-10 text-center text-sm text-slate-400">Todavía no hay mensajes.</x-card>
        @endforelse

        {{ $mensajes->links() }}
    </div>
</x-app-layout>
