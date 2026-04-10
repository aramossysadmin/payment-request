<?php

namespace App\Filament\Resources\InvestmentRequestResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvals';

    protected static ?string $title = 'Autorizaciones';

    protected static ?string $modelLabel = 'Autorización';

    protected static ?string $pluralModelLabel = 'Autorizaciones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('Etapa')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'department' => 'warning',
                        'administration' => 'info',
                        'treasury' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'department' => 'Departamento',
                        'administration' => 'Administración',
                        'treasury' => 'Tesorería',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autorizador')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('comments')
                    ->label('Comentarios')
                    ->limit(50)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('responded_at')
                    ->label('Fecha de Respuesta')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
