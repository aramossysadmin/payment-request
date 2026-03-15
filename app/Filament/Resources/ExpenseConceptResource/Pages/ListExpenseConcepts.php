<?php

namespace App\Filament\Resources\ExpenseConceptResource\Pages;

use App\Filament\Resources\ExpenseConceptResource;
use App\Models\ExpenseConcept;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExpenseConcepts extends ListRecords
{
    protected static string $resource = ExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Descargar Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    return response()->streamDownload(function () {
                        $csv = Writer::createFromString();
                        $csv->insertOne(['Nombre']);
                        $csv->insertOne(['PAPELERIA (EJEMPLO - ELIMINAR ESTA FILA ANTES DE IMPORTAR)']);
                        $csv->insertOne(['TRANSPORTE (EJEMPLO - ELIMINAR ESTA FILA ANTES DE IMPORTAR)']);
                        echo $csv->toString();
                    }, 'template_conceptos_de_gasto.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            Actions\Action::make('importCsv')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('csv_file')
                        ->label('Archivo CSV')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required()
                        ->disk('local')
                        ->directory('csv-imports')
                        ->helperText('Suba un archivo CSV con una columna "Nombre". Descargue el template para ver el formato correcto.'),
                ])
                ->modalHeading('Importar Conceptos de Gasto')
                ->modalDescription('Suba un archivo CSV con los conceptos de gasto a importar. Puede descargar el template de ejemplo para ver el formato correcto.')
                ->modalSubmitActionLabel('Importar')
                ->action(function (array $data): void {
                    $this->processImport($data['csv_file']);
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function processImport(string $filePath): void
    {
        $fullPath = Storage::disk('local')->path($filePath);

        try {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            $headers = $csv->getHeader();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al leer el archivo')
                ->body('El archivo no es un CSV válido. Descargue el template y utilice el formato correcto.')
                ->danger()
                ->persistent()
                ->send();

            Storage::disk('local')->delete($filePath);

            return;
        }

        $normalizedHeaders = array_map(fn (string $header): string => mb_strtolower(trim($header)), $headers);

        if (! in_array('nombre', $normalizedHeaders)) {
            Notification::make()
                ->title('Formato de archivo incorrecto')
                ->body('El archivo CSV debe contener una columna "Nombre". Descargue el template para ver el formato correcto.')
                ->danger()
                ->persistent()
                ->send();

            Storage::disk('local')->delete($filePath);

            return;
        }

        $nameColumnIndex = array_search('nombre', $normalizedHeaders);
        $nameColumnKey = $headers[$nameColumnIndex];

        $imported = 0;
        $skipped = [];
        $errors = [];

        foreach ($csv->getRecords() as $index => $record) {
            $rawName = trim($record[$nameColumnKey] ?? '');

            if ($rawName === '') {
                continue;
            }

            $name = mb_strtoupper($rawName);

            if (str_contains($name, '(EJEMPLO')) {
                continue;
            }

            if (mb_strlen($name) > 255) {
                $errors[] = "Fila {$index}: \"{$name}\" excede 255 caracteres.";

                continue;
            }

            $existing = ExpenseConcept::withTrashed()->where('name', $name)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $skipped[] = "{$name} (eliminado previamente - restáurelo desde el panel)";
                } elseif (! $existing->is_active) {
                    $skipped[] = "{$name} (existe pero está inactivo)";
                } else {
                    $skipped[] = "{$name} (ya existe)";
                }

                continue;
            }

            ExpenseConcept::create(['name' => $name]);
            $imported++;
        }

        Storage::disk('local')->delete($filePath);

        $bodyParts = [];
        $bodyParts[] = "Se importaron {$imported} concepto(s) exitosamente.";

        if (count($skipped) > 0) {
            $bodyParts[] = '';
            $bodyParts[] = 'Conceptos omitidos:';
            foreach ($skipped as $skip) {
                $bodyParts[] = "• {$skip}";
            }
        }

        if (count($errors) > 0) {
            $bodyParts[] = '';
            $bodyParts[] = 'Errores:';
            foreach ($errors as $error) {
                $bodyParts[] = "• {$error}";
            }
        }

        $type = count($errors) > 0 ? 'warning' : ($imported > 0 ? 'success' : 'info');

        Notification::make()
            ->title('Importación finalizada')
            ->body(implode("\n", $bodyParts))
            ->$type()
            ->persistent()
            ->send();
    }
}
