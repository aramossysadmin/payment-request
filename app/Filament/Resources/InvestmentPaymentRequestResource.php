<?php

namespace App\Filament\Resources;

use App\Enums\InvestmentPaymentType;
use App\Filament\Resources\InvestmentPaymentRequestResource\Pages;
use App\Models\Department;
use App\Models\InvestmentPaymentRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvestmentPaymentRequestResource extends Resource
{
    protected static ?string $model = InvestmentPaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Auditoría';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pago de Inversión';

    protected static ?string $pluralModelLabel = 'Pagos de Inversión';

    protected static ?string $recordTitleAttribute = 'folio_number';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información General')
                ->columns(3)
                ->schema([
                    Placeholder::make('folio_number')
                        ->label('Folio')
                        ->content(fn (InvestmentPaymentRequest $record): string => '#'.str_pad((string) $record->folio_number, 5, '0', STR_PAD_LEFT)),
                    Placeholder::make('status')
                        ->label('Estado')
                        ->content(fn (InvestmentPaymentRequest $record): string => self::statusLabel($record->status)),
                    Placeholder::make('payment_type')
                        ->label('Tipo de Pago')
                        ->content(fn (InvestmentPaymentRequest $record): string => InvestmentPaymentType::labelFor($record->payment_type)),
                    Placeholder::make('user')
                        ->label('Solicitante')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->user?->name ?? '—'),
                    Placeholder::make('department')
                        ->label('Departamento')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->department?->name ?? '—'),
                    Placeholder::make('batch')
                        ->label('Lote (Batch)')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->batch_id ? '#'.$record->batch_id.' — Semana '.($record->batch?->week_number ?? '?').' ('.($record->batch?->year ?? '?').')' : 'Sin lote (flujo anterior)'),
                    Placeholder::make('investment_request')
                        ->label('Concepto de Inversión')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->investmentRequest?->investmentExpenseConcept?->name ?? '—'),
                    Placeholder::make('provider')
                        ->label('Proveedor')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->provider.($record->rfc ? ' ('.$record->rfc.')' : '')),
                    Placeholder::make('branch')
                        ->label('Sucursal')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->branch?->name ?? '—'),
                ]),

            Section::make('Montos')
                ->columns(3)
                ->schema([
                    Placeholder::make('subtotal')
                        ->label('Subtotal')
                        ->content(fn (InvestmentPaymentRequest $record): string => '$ '.number_format((float) $record->subtotal, 2).' '.($record->currency?->prefix ?? 'MXN')),
                    Placeholder::make('iva')
                        ->label('IVA')
                        ->content(fn (InvestmentPaymentRequest $record): string => '$ '.number_format((float) $record->iva, 2)),
                    Placeholder::make('total')
                        ->label('Total Solicitado')
                        ->content(fn (InvestmentPaymentRequest $record): string => '$ '.number_format((float) $record->total, 2)),
                    Placeholder::make('approved_amount')
                        ->label('Monto Aprobado por PM')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->approved_amount !== null
                            ? '$ '.number_format((float) $record->approved_amount, 2).' '.((float) $record->approved_amount < (float) $record->total ? '(ajustado de $'.number_format((float) $record->total, 2).')' : '')
                            : '—'),
                    Placeholder::make('payment_provision_date')
                        ->label('Fecha de pago')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->payment_provision_date?->format('Y-m-d') ?? '—'),
                    Placeholder::make('payment_week_number')
                        ->label('Semana de pago')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->payment_week_number ? 'Semana '.$record->payment_week_number : '—'),
                ]),

            Section::make('Timeline del Flujo')
                ->columns(2)
                ->schema([
                    Placeholder::make('created_at')
                        ->label('Capturado')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->created_at?->format('Y-m-d H:i:s') ?? '—'),
                    Placeholder::make('ceo_reviewed_at')
                        ->label('Revisado por CEO')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->ceo_reviewed_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('pm_reviewed_at')
                        ->label('Revisado por Project Manager')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->pm_reviewed_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('final_reviewed_at')
                        ->label('Aprobación Final')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->final_reviewed_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('updated_at')
                        ->label('Última actualización')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->updated_at?->format('Y-m-d H:i:s') ?? '—'),
                    Placeholder::make('documents')
                        ->label('Documentos cargados (Fase 5)')
                        ->content(fn (InvestmentPaymentRequest $record): string => is_array($record->advance_documents) && count($record->advance_documents) > 0
                            ? count($record->advance_documents).' archivo(s)'
                            : 'Sin documentos'),
                ]),

            Section::make('Motivos de Rechazo')
                ->columns(1)
                ->visible(fn (InvestmentPaymentRequest $record): bool => $record->ceo_rejection_reason !== null
                    || $record->pm_rejection_reason !== null
                    || $record->final_rejection_reason !== null)
                ->schema([
                    Placeholder::make('ceo_rejection_reason')
                        ->label('Rechazo CEO')
                        ->visible(fn (InvestmentPaymentRequest $record): bool => $record->ceo_rejection_reason !== null)
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->ceo_rejection_reason ?? '—'),
                    Placeholder::make('pm_rejection_reason')
                        ->label('Rechazo Project Manager')
                        ->visible(fn (InvestmentPaymentRequest $record): bool => $record->pm_rejection_reason !== null)
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->pm_rejection_reason ?? '—'),
                    Placeholder::make('final_rejection_reason')
                        ->label('Rechazo Final (histórico)')
                        ->visible(fn (InvestmentPaymentRequest $record): bool => $record->final_rejection_reason !== null)
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->final_rejection_reason ?? '—'),
                ]),

            Section::make('Descripción')
                ->visible(fn (InvestmentPaymentRequest $record): bool => $record->description !== null)
                ->schema([
                    Placeholder::make('description')
                        ->label('')
                        ->content(fn (InvestmentPaymentRequest $record): string => $record->description ?? '—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio_number')
                    ->label('Folio')
                    ->formatStateUsing(fn (int $state): string => '#'.str_pad((string) $state, 5, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state))
                    ->colors(self::statusColors())
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('investmentRequest.investmentExpenseConcept.name')
                    ->label('Concepto')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Proveedor')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Aprobado')
                    ->money('MXN')
                    ->placeholder('—')
                    ->color(fn (?string $state, InvestmentPaymentRequest $record): ?string => $state !== null && (float) $state < (float) $record->total ? 'warning' : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('batch_id')
                    ->label('Lote')
                    ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : 'Legacy')
                    ->color(fn (?int $state): ?string => $state ? null : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Capturado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'submitted' => 'Enviado',
                        'ceo_approved' => 'CEO Aprobó',
                        'ceo_rejected' => 'CEO Rechazó',
                        'projectmanager_approved' => 'PM Aprobó (histórico)',
                        'projectmanager_rejected' => 'PM Rechazó',
                        'final_approved' => 'Aprobado Final',
                        'final_rejected' => 'Rechazado Final (histórico)',
                        'completed' => 'Completado',
                        'scheduled_for_bank' => 'Programado en banco',
                        'receipt_attached' => 'Comprobante adjunto',
                        'approved' => 'Aprobado (Legacy)',
                        'rejected' => 'Rechazado (Legacy)',
                        'pending_approval' => 'Pendiente (Legacy)',
                    ]),
                SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('batch_id')
                    ->label('Tipo')
                    ->options([
                        'with_batch' => 'Con lote (flujo nuevo)',
                        'without_batch' => 'Sin lote (flujo anterior)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || $data['value'] === '') {
                            return $query;
                        }

                        return $data['value'] === 'with_batch'
                            ? $query->whereNotNull('batch_id')
                            : $query->whereNull('batch_id');
                    }),
                Filter::make('created_at')
                    ->label('Fecha de captura')
                    ->form([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('to')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestmentPaymentRequests::route('/'),
            'view' => Pages\ViewInvestmentPaymentRequest::route('/{record}'),
        ];
    }

    // Solo lectura: bloquear create/edit/delete
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Borrador',
            'submitted' => 'Enviado',
            'ceo_approved' => 'CEO Aprobó',
            'ceo_rejected' => 'CEO Rechazó',
            'projectmanager_review' => 'En revisión PM',
            'projectmanager_approved' => 'PM Aprobó (histórico)',
            'projectmanager_rejected' => 'PM Rechazó',
            'final_pending' => 'Pendiente Final (histórico)',
            'final_approved' => 'Aprobado Final',
            'final_rejected' => 'Rechazado Final',
            'documents_pending' => 'Esperando docs',
            'completed' => 'Completado',
            'scheduled_for_bank' => 'Programado en banco',
            'receipt_attached' => 'Comprobante adjunto',
            'approved' => 'Aprobado (Legacy)',
            'rejected' => 'Rechazado (Legacy)',
            'pending_approval' => 'Pendiente (Legacy)',
            default => $status,
        };
    }

    /**
     * @return array<string, string>
     */
    private static function statusColors(): array
    {
        return [
            'gray' => ['draft', 'submitted', 'projectmanager_review', 'documents_pending', 'pending_approval'],
            'success' => ['final_approved', 'completed', 'approved', 'scheduled_for_bank', 'receipt_attached'],
            'warning' => ['ceo_approved', 'projectmanager_approved', 'final_pending'],
            'danger' => ['ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'rejected'],
        ];
    }
}
