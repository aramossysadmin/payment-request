@extends('approval.layout', ['title' => $success ? 'Acción completada' : 'Error'])

@section('content')
    <div class="bg-brand-card dark:bg-brand-card-dark rounded-xl shadow-sm border border-brand-border dark:border-brand-border-dark overflow-hidden text-center px-6 py-10">
        @if($success)
            <div class="mx-auto w-16 h-16 bg-navy/10 dark:bg-cream/10 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-navy dark:text-cream" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        @else
            <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-500/10 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        @endif

        <h1 class="text-xl font-bold text-navy dark:text-cream mb-2">
            {{ $success ? 'Acción completada' : 'No se pudo procesar' }}
        </h1>
        <p class="text-brand-muted dark:text-brand-muted-dark text-sm">{{ $message }}</p>

        @if($success && isset($folioNumber, $provider, $action))
            <div class="mt-6 bg-brand-secondary/50 dark:bg-brand-secondary-dark/50 rounded-lg px-4 py-3 text-sm text-left space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-brand-muted dark:text-brand-muted-dark">Folio</span>
                    <span class="font-semibold text-navy dark:text-cream">#{{ $folioNumber }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted dark:text-brand-muted-dark">Proveedor</span>
                    <span class="font-semibold text-navy dark:text-cream">{{ $provider }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-muted dark:text-brand-muted-dark">Acción</span>
                    @if($action === 'approved')
                        <span class="font-semibold text-green-600 dark:text-green-400">Autorizada</span>
                    @else
                        <span class="font-semibold text-red-600 dark:text-red-400">Rechazada</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
