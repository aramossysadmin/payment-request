@extends('approval.layout', ['title' => 'Revisar Solicitud #' . $paymentRequest->folio_number])

@section('content')
    <div class="bg-brand-card dark:bg-brand-card-dark rounded-xl shadow-sm border border-brand-border dark:border-brand-border-dark overflow-hidden">
        {{-- Header --}}
        <div class="bg-navy px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-cream/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-cream" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-cream">Solicitud de Pago #{{ $paymentRequest->folio_number }}</h1>
                    <p class="text-cream/40 text-[10px] sm:text-xs font-mono break-all mt-0.5">{{ $paymentRequest->uuid }}</p>
                    <p class="text-cream/60 text-sm mt-1">Requiere tu autorización</p>
                </div>
            </div>
        </div>

        <div class="divide-y divide-brand-secondary dark:divide-brand-border-dark">
            {{-- Información General --}}
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Información General</h2>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Solicitante</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->user->name }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Departamento</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->department->name }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Etapa</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">
                            @switch($approval->stage)
                                @case('department') Departamento @break
                                @case('administration') Administración @break
                                @case('treasury') Tesorería @break
                            @endswitch
                            - Nivel {{ $approval->level }}
                        </p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Fecha de Creación</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->created_at->translatedFormat('d \d\e F \d\e Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Detalles de la Solicitud --}}
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Detalles de la Solicitud</h2>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="col-span-2">
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Sucursal</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->branch->name ?? '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Concepto de Gasto</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">
                            @if($paymentRequest instanceof \App\Models\InvestmentRequest)
                                {{ $paymentRequest->investmentExpenseConcept->name ?? $paymentRequest->expenseConcept->name ?? '-' }}
                            @else
                                {{ $paymentRequest->expenseConcept->name ?? '-' }}
                            @endif
                        </p>
                    </div>
                    @if(!($paymentRequest instanceof \App\Models\InvestmentRequest))
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Tipo de Pago</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->paymentType->name ?? '-' }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Moneda</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->currency->prefix ?? '-' }}</p>
                    </div>
                </div>
                @if($paymentRequest->description)
                    <div class="text-sm">
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Descripción</span>
                        <p class="font-medium text-navy dark:text-cream mt-0.5">{{ $paymentRequest->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Datos del Proveedor --}}
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Datos del Proveedor</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Proveedor</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->provider }}</p>
                    </div>
                    <div>
                        <span class="text-brand-muted dark:text-brand-muted-dark text-xs">Folio Factura</span>
                        <p class="font-semibold text-navy dark:text-cream mt-0.5">{{ $paymentRequest->invoice_folio }}</p>
                    </div>
                </div>
            </div>

            {{-- Montos --}}
            @php $currencyCode = $paymentRequest->currency->prefix ?? 'MXN'; @endphp
            <div class="px-6 py-4 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Montos</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-brand-muted dark:text-brand-muted-dark">Subtotal</span>
                        <span class="text-navy dark:text-cream font-medium">$ {{ number_format($paymentRequest->subtotal, 2) }} {{ $currencyCode }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-brand-muted dark:text-brand-muted-dark">IVA</span>
                        <span class="text-navy dark:text-cream font-medium">$ {{ number_format($paymentRequest->iva, 2) }} {{ $currencyCode }}</span>
                    </div>
                    @if($paymentRequest->retention)
                        <div class="flex justify-between">
                            <span class="text-brand-muted dark:text-brand-muted-dark">Retención</span>
                            <span class="text-navy dark:text-cream font-medium">Sí</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t border-brand-secondary dark:border-brand-border-dark pt-3">
                        <span class="text-navy dark:text-cream">Total</span>
                        <span class="text-gold">$ {{ number_format($paymentRequest->total, 2) }} {{ $currencyCode }}</span>
                    </div>
                </div>

                <div class="flex items-start gap-2 text-xs text-gold bg-gold/10 rounded-lg px-3 py-2.5">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>Los folios SAP deberán capturarse desde el panel de administración.</span>
                </div>
            </div>
        </div>

        {{-- Documentos --}}
            @if($paymentRequest->advance_documents && count(array_filter($paymentRequest->advance_documents, fn($doc) => is_string($doc) && $doc !== '')) > 0)
                <div class="px-6 py-4 space-y-3">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-brand-muted dark:text-brand-muted-dark">Documentos Adjuntos</h2>
                    <div class="space-y-2">
                        @foreach(array_filter($paymentRequest->advance_documents, fn($doc) => is_string($doc) && $doc !== '') as $doc)
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('documents.view', now()->addHours(48), ['path' => $doc]) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="flex items-center gap-3 rounded-lg border border-brand-border dark:border-brand-border-dark px-3 py-2.5 hover:bg-brand-secondary/50 dark:hover:bg-brand-secondary-dark/50 transition-colors group">
                                <svg class="w-5 h-5 text-gold shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span class="text-sm font-medium text-navy dark:text-cream group-hover:text-gold transition-colors">{{ basename($doc) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        {{-- Actions --}}
        <div class="px-6 py-5 bg-brand-secondary/50 dark:bg-brand-secondary-dark/50 border-t border-brand-secondary dark:border-brand-border-dark space-y-3">
            {{-- Approve toggle --}}
            <button type="button"
                id="approve-toggle"
                class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2"
                onclick="document.getElementById('approve-section').classList.remove('hidden'); this.classList.add('hidden'); document.getElementById('reject-toggle').classList.add('hidden');">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Autorizar Solicitud
            </button>

            {{-- Approve confirmation (hidden by default) --}}
            <div id="approve-section" class="hidden space-y-3">
                <p class="text-sm text-center text-navy dark:text-cream font-medium">¿Estás seguro de que deseas autorizar esta solicitud?</p>
                <form method="POST" action="{{ route('approval.approve', $token) }}" id="approve-form">
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

            {{-- Reject toggle --}}
            <button type="button"
                id="reject-toggle"
                class="w-full bg-white hover:bg-red-50 dark:bg-transparent dark:hover:bg-red-500/10 text-red-600 dark:text-red-400 font-semibold py-3 px-4 rounded-lg border-2 border-red-400 dark:border-red-500/50 transition-colors focus:outline-none focus:ring-2 focus:ring-gold focus:ring-offset-2 dark:focus:ring-offset-brand-card-dark flex items-center justify-center gap-2"
                onclick="document.getElementById('reject-section').classList.remove('hidden'); this.classList.add('hidden'); document.getElementById('approve-toggle').classList.add('hidden');">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Rechazar Solicitud
            </button>

            {{-- Reject form (hidden by default) --}}
            <div id="reject-section" class="hidden space-y-3">
                <form method="POST" action="{{ route('approval.reject', $token) }}" id="reject-form">
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
