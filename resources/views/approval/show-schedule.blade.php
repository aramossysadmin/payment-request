@extends('approval.layout', ['title' => 'Programación de Pagos - Semana ' . $schedule->week_number . '/' . $schedule->year])

@section('content')
    <div class="bg-brand-card dark:bg-brand-card-dark rounded-xl shadow-sm border border-brand-border dark:border-brand-border-dark overflow-hidden">
        {{-- Header --}}
        <div class="bg-navy px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cream/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-cream" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-cream">Programación de Pagos Semanal</h1>
                    <p class="text-cream/60 text-sm mt-1">Semana {{ $schedule->week_number }} / {{ $schedule->year }}</p>
                    <p class="text-cream/60 text-sm mt-0.5">Requiere tu autorización</p>
                </div>
            </div>
        </div>

        <div class="divide-y divide-brand-secondary dark:divide-brand-border-dark">
            {{-- Información General --}}
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Información General</h2>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Creado por</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $schedule->creator->name ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Fecha de Creación</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $schedule->created_at->translatedFormat('d \d\e F \d\e Y') }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Pagos incluidos</span>
                        <p class="font-semibold text-green-600 dark:text-green-400 mt-0.5">{{ $schedule->items->where('included', true)->count() }} de {{ $schedule->items->count() }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Pagos excluidos</span>
                        <p class="font-semibold text-red-600 dark:text-red-400 mt-0.5">{{ $schedule->items->where('included', false)->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Detalle de Pagos --}}
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Detalle de Pagos</h2>
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-brand-border dark:border-brand-border-dark">
                                <th class="pb-2 pr-3 text-left font-semibold text-brand-muted dark:text-brand-muted-dark">Folio</th>
                                <th class="pb-2 pr-3 text-left font-semibold text-brand-muted dark:text-brand-muted-dark">Proveedor</th>
                                <th class="pb-2 pr-3 text-right font-semibold text-brand-muted dark:text-brand-muted-dark">Monto</th>
                                <th class="pb-2 text-center font-semibold text-brand-muted dark:text-brand-muted-dark">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedule->items as $item)
                                @php $payment = $item->investmentPaymentRequest; @endphp
                                <tr class="border-b border-brand-secondary dark:border-brand-border-dark last:border-0 {{ !$item->included ? 'opacity-60' : '' }}">
                                    <td class="py-2 pr-3 font-mono text-navy dark:text-cream">#{{ str_pad($payment->folio_number ?? 0, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td class="py-2 pr-3 text-navy dark:text-cream">{{ $payment->provider ?? '-' }}</td>
                                    <td class="py-2 pr-3 text-right font-semibold text-navy dark:text-cream">$ {{ number_format($payment->total ?? 0, 2) }}</td>
                                    <td class="py-2 text-center">
                                        @if($item->included)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-green-700 dark:text-green-400 font-medium">
                                                &#10003; Incluido
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-red-700 dark:text-red-400 font-medium">
                                                &#10007; Excluido
                                            </span>
                                            @if($item->exclusion_reason)
                                                <p class="text-brand-muted dark:text-brand-muted-dark mt-0.5">{{ $item->exclusion_reason }}</p>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Total --}}
            @php
                $totalIncluded = $schedule->items->where('included', true)->sum(fn($item) => (float) ($item->investmentPaymentRequest?->total ?? 0));
            @endphp
            <div class="px-6 py-4">
                <div class="flex justify-between font-bold text-base">
                    <span class="text-navy dark:text-cream">Total a procesar en bancos</span>
                    <span class="text-gold">$ {{ number_format($totalIncluded, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="px-6 py-5 bg-brand-secondary/50 dark:bg-brand-secondary-dark/50 border-t border-brand-secondary dark:border-brand-border-dark space-y-3">
            <button type="button"
                id="approve-toggle"
                class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2"
                onclick="document.getElementById('approve-section').classList.remove('hidden'); this.classList.add('hidden'); document.getElementById('reject-toggle').classList.add('hidden');">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Autorizar Programación
            </button>

            <div id="approve-section" class="hidden space-y-3">
                <p class="text-sm text-center text-navy dark:text-cream font-medium">¿Estás seguro de que deseas autorizar esta programación de pagos?</p>
                <form method="POST" action="{{ route('approval.approve', $token) }}">
                    @csrf
                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Sí, autorizar
                    </button>
                </form>
                <button type="button"
                    class="w-full text-brand-muted dark:text-brand-muted-dark hover:text-navy dark:hover:text-cream text-sm py-2 transition-colors"
                    onclick="document.getElementById('approve-section').classList.add('hidden'); document.getElementById('approve-toggle').classList.remove('hidden'); document.getElementById('reject-toggle').classList.remove('hidden');">
                    Cancelar
                </button>
            </div>

            <button type="button"
                id="reject-toggle"
                class="w-full bg-white hover:bg-red-50 dark:bg-transparent dark:hover:bg-red-500/10 text-red-600 dark:text-red-400 font-semibold py-3 px-4 rounded-lg border-2 border-red-400 dark:border-red-500/50 transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2"
                onclick="document.getElementById('reject-section').classList.remove('hidden'); this.classList.add('hidden'); document.getElementById('approve-toggle').classList.add('hidden');">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Rechazar Programación
            </button>

            <div id="reject-section" class="hidden space-y-3">
                <form method="POST" action="{{ route('approval.reject', $token) }}">
                    @csrf
                    <div class="space-y-2">
                        <label for="comments" class="block text-sm font-medium text-navy dark:text-cream">
                            Motivo del rechazo <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="comments"
                            name="comments"
                            rows="3"
                            required
                            minlength="10"
                            placeholder="Escribe el motivo del rechazo (mínimo 10 caracteres)..."
                            class="w-full rounded-lg border border-brand-border dark:border-brand-border-dark bg-brand-card dark:bg-brand-bg-dark px-3 py-2 text-sm text-navy dark:text-cream placeholder-brand-muted/50 dark:placeholder-brand-muted-dark/50 focus:border-gold focus:ring-2 focus:ring-gold/30 outline-none resize-none"
                        >{{ old('comments') }}</textarea>
                        @error('comments')
                            <p class="text-red-500 dark:text-red-400 text-xs">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="w-full mt-3 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        Confirmar Rechazo
                    </button>
                </form>
                <button type="button"
                    class="w-full text-brand-muted dark:text-brand-muted-dark hover:text-navy dark:hover:text-cream text-sm py-2 transition-colors"
                    onclick="document.getElementById('reject-section').classList.add('hidden'); document.getElementById('reject-toggle').classList.remove('hidden'); document.getElementById('approve-toggle').classList.remove('hidden');">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
@endsection
