<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentPaymentBatchResource\Pages;
use App\Models\Department;
use App\Models\InvestmentPaymentBatch;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InvestmentPaymentBatchResource extends Resource
{
    protected static ?string $model = InvestmentPaymentBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Auditoría';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Lote de Pagos';

    protected static ?string $pluralModelLabel = 'Lotes de Pagos';

    protected static ?string $recordTitleAttribute = 'uuid';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información del Lote')
                ->columns(3)
                ->schema([
                    Placeholder::make('id')
                        ->label('ID')
                        ->content(fn (InvestmentPaymentBatch $record): string => '#'.$record->id),
                    Placeholder::make('uuid')
                        ->label('UUID')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->uuid),
                    Placeholder::make('status')
                        ->label('Estado')
                        ->content(fn (InvestmentPaymentBatch $record): string => self::statusLabel($record->status)),
                    Placeholder::make('department')
                        ->label('Departamento')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->department?->name ?? '—'),
                    Placeholder::make('project')
                        ->label('Proyecto')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->project?->name ?? '—'),
                    Placeholder::make('user')
                        ->label('Solicitante')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->user?->name ?? '—'),
                    Placeholder::make('week')
                        ->label('Semana / Año')
                        ->content(fn (InvestmentPaymentBatch $record): string => 'Semana '.$record->week_number.' ('.$record->year.')'),
                    Placeholder::make('payment_count')
                        ->label('Pagos en el lote')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->paymentRequests()->count().' pago(s)'),
                    Placeholder::make('total')
                        ->label('Total del lote')
                        ->content(fn (InvestmentPaymentBatch $record): string => '$ '.number_format(
                            (float) $record->paymentRequests()->sum(DB::raw('COALESCE(approved_amount, total)')),
                            2
                        ).' MXN'),
                ]),

            Section::make('Timeline del Lote')
                ->columns(2)
                ->schema([
                    Placeholder::make('created_at')
                        ->label('Creado')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->created_at?->format('Y-m-d H:i:s') ?? '—'),
                    Placeholder::make('submitted_at')
                        ->label('Enviado a aprobación CEO')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->submitted_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('ceo_reviewed_at')
                        ->label('Revisado por CEO')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->ceo_reviewed_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('final_ceo_reviewed_at')
                        ->label('Aprobación final (PM)')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->final_ceo_reviewed_at?->format('Y-m-d H:i:s') ?? 'Pendiente'),
                    Placeholder::make('deadline_at')
                        ->label('Fecha límite (futuro)')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->deadline_at?->format('Y-m-d H:i:s') ?? 'Sin definir'),
                    Placeholder::make('updated_at')
                        ->label('Última actualización')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->updated_at?->format('Y-m-d H:i:s') ?? '—'),
                ]),

            Section::make('Estado de Tokens de Aprobación')
                ->columns(2)
                ->schema([
                    Placeholder::make('ceo_approval_token')
                        ->label('Token CEO')
                        ->content(fn (InvestmentPaymentBatch $record): string => self::tokenStatus(
                            $record->ceo_approval_token,
                            $record->ceo_approval_token_expires_at
                        )),
                    Placeholder::make('ceo_approval_token_expires_at')
                        ->label('Expira CEO Token')
                        ->content(fn (InvestmentPaymentBatch $record): string => $record->ceo_approval_token_expires_at?->format('Y-m-d H:i:s') ?? '—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(fn (int $state): string => '#'.$state)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state))
                    ->colors(self::statusColors())
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('week_number')
                    ->label('Semana / Año')
                    ->formatStateUsing(fn (int $state, InvestmentPaymentBatch $record): string => 'S'.$state.'/'.$record->year)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('payment_requests_count')
                    ->label('Pagos')
                    ->counts('paymentRequests')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Enviado')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ceo_reviewed_at')
                    ->label('CEO')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('final_ceo_reviewed_at')
                    ->label('Aprob. final (PM)')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'submitted' => 'Enviado a CEO',
                        'ceo_approved' => 'CEO Aprobó',
                        'ceo_rejected' => 'CEO Rechazó',
                        'projectmanager_rejected' => 'PM Rechazó',
                        'final_approved' => 'Aprobado Final',
                        'final_rejected' => 'Rechazado Final (histórico)',
                    ]),
                SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->options(fn (): array => Project::orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('year')
                    ->label('Año')
                    ->options(fn (): array => InvestmentPaymentBatch::query()
                        ->distinct()
                        ->orderByDesc('year')
                        ->pluck('year', 'year')
                        ->toArray()),
                Filter::make('week_number')
                    ->label('Semana')
                    ->form([
                        TextInput::make('week')
                            ->label('Número de semana')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(53),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['week'] ?? null,
                            fn (Builder $q, int $week) => $q->where('week_number', $week)
                        );
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
            'index' => Pages\ListInvestmentPaymentBatches::route('/'),
            'view' => Pages\ViewInvestmentPaymentBatch::route('/{record}'),
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
            'submitted' => 'Enviado a CEO',
            'ceo_approved' => 'CEO Aprobó',
            'ceo_rejected' => 'CEO Rechazó',
            'projectmanager_review' => 'En revisión PM',
            'projectmanager_approved' => 'PM Aprobó',
            'projectmanager_rejected' => 'PM Rechazó',
            'final_pending' => 'Pendiente Final (histórico)',
            'final_approved' => 'Aprobado Final',
            'final_rejected' => 'Rechazado Final',
            default => $status,
        };
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private static function statusColors(): array
    {
        return [
            'gray' => ['draft', 'submitted', 'projectmanager_review'],
            'success' => ['final_approved'],
            'warning' => ['ceo_approved', 'projectmanager_approved', 'final_pending'],
            'danger' => ['ceo_rejected', 'projectmanager_rejected', 'final_rejected'],
        ];
    }

    private static function tokenStatus(?string $token, ?Carbon $expiresAt): string
    {
        if (! $token) {
            return 'Sin token (no se generó o ya se consumió)';
        }

        if (! $expiresAt) {
            return 'Token activo (sin expiración)';
        }

        if ($expiresAt->isPast()) {
            return 'Token EXPIRADO el '.$expiresAt->format('Y-m-d H:i:s');
        }

        return 'Token activo, expira el '.$expiresAt->format('Y-m-d H:i:s');
    }
}
