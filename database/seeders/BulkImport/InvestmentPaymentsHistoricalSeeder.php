<?php

namespace Database\Seeders\BulkImport;

use App\Enums\IvaRate;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\InvestmentPaymentApproval;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\User;
use App\States\InvestmentRequest\Completed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class InvestmentPaymentsHistoricalSeeder extends Seeder
{
    private const TSV_FILENAME = 'investment_payments_historical.tsv';

    /**
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        'investment_request_folio',
        'provider',
        'payment_type',
        'payment_provision_date',
        'subtotal',
        'iva_rate',
        'iva',
        'total',
        'created_by_email',
    ];

    /**
     * Total cents fractions that are treated as CFDI rounding artifacts.
     * Used to auto-correct totals like 18,200.01 → 18,200.00 when iva was rounded up.
     *
     * @var list<int>
     */
    private const ROUNDING_ARTIFACT_CENTS = [1, 2, 98, 99];

    /**
     * @var list<string>
     */
    private const VALID_PAYMENT_TYPES = ['factura', 'anticipo'];

    public function run(): void
    {
        $isDryRun = (bool) env('DRY_RUN', false);
        $absolutePath = database_path('seeders/data/'.self::TSV_FILENAME);

        if (! is_file($absolutePath)) {
            $this->command->error("Archivo no encontrado en {$absolutePath}");

            return;
        }

        $reader = Reader::createFromPath($absolutePath, 'r');
        $reader->setDelimiter("\t");
        $reader->setHeaderOffset(0);
        $headers = array_map(
            fn (string $h): string => mb_strtolower(trim($h)),
            $reader->getHeader()
        );

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                $this->command->error("El TSV debe contener la columna \"{$required}\".");

                return;
            }
        }

        $catalogs = [
            'currencies' => $this->indexByName(Currency::query()->pluck('id', 'name')),
            'branches' => $this->indexByName(Branch::query()->pluck('id', 'name')),
            'users' => User::query()->pluck('id', 'email')
                ->mapWithKeys(fn (int $id, string $email): array => [mb_strtolower($email) => $id])
                ->all(),
        ];

        $userDepartments = User::query()->pluck('department_id', 'id')->all();

        $allParents = InvestmentRequest::query()->get();
        $parentsByFolio = $allParents->keyBy('folio_number');

        [$groupBudgets, $groupParentIds] = $this->buildGroupBudgets($allParents);
        $groupPaid = $this->buildGroupPaid($groupParentIds);

        $existingFingerprints = $this->buildExistingFingerprints();

        $authorizer = User::query()
            ->where('email', config('investment-requests.authorizer_email'))
            ->first();

        if (! $authorizer) {
            $this->command->error('Authorizer no encontrado: '.config('investment-requests.authorizer_email'));

            return;
        }

        $imported = 0;
        $skippedDuplicates = [];
        $errors = [];
        $adjustments = [];
        $batchGroupSpend = [];
        $rowsToCreate = [];

        DB::beginTransaction();

        try {
            foreach ($reader->getRecords() as $rowIndex => $record) {
                $row = $this->normalizeKeys($record);
                $rowNum = (int) $rowIndex + 1;

                $folioRaw = trim((string) ($row['investment_request_folio'] ?? ''));
                if ($folioRaw === '' || ! ctype_digit($folioRaw)) {
                    $errors[] = "Fila {$rowNum}: investment_request_folio inválido: \"{$folioRaw}\".";

                    continue;
                }
                $folioNum = (int) $folioRaw;

                /** @var InvestmentRequest|null $parent */
                $parent = $parentsByFolio->get($folioNum);
                if (! $parent) {
                    $errors[] = "Fila {$rowNum}: folio padre {$folioNum} no existe en investment_requests.";

                    continue;
                }

                if ($parent->status::class !== Completed::class) {
                    $errors[] = "Fila {$rowNum}: folio padre {$folioNum} no está en estatus Completed (estado actual: ".class_basename($parent->status).').';

                    continue;
                }

                $paymentType = mb_strtolower(trim((string) ($row['payment_type'] ?? '')));
                if (! in_array($paymentType, self::VALID_PAYMENT_TYPES, true)) {
                    $errors[] = "Fila {$rowNum}: payment_type inválido: \"{$paymentType}\". Valores permitidos: ".implode(', ', self::VALID_PAYMENT_TYPES).'.';

                    continue;
                }

                $ivaRateRaw = trim((string) ($row['iva_rate'] ?? ''));
                $ivaRate = IvaRate::tryFrom($ivaRateRaw);
                if ($ivaRate === null) {
                    $errors[] = "Fila {$rowNum}: iva_rate inválido: \"{$ivaRateRaw}\". Valores permitidos: ".implode(', ', array_column(IvaRate::cases(), 'value')).'.';

                    continue;
                }

                $subtotal = (float) trim((string) ($row['subtotal'] ?? '0'));
                $iva = (float) trim((string) ($row['iva'] ?? '0'));
                $total = (float) trim((string) ($row['total'] ?? '0'));

                $correction = $this->applyCfdiRoundingCorrection($subtotal, $ivaRate->rate(), $iva, $total);
                if ($correction['adjusted']) {
                    $adjustments[] = [
                        'row' => $rowNum,
                        'folio' => $folioNum,
                        'original' => ['subtotal' => $subtotal, 'iva' => $iva, 'total' => $total],
                        'corrected' => ['subtotal' => $correction['subtotal'], 'iva' => $correction['iva'], 'total' => $correction['total']],
                    ];
                    $subtotal = $correction['subtotal'];
                    $iva = $correction['iva'];
                    $total = $correction['total'];
                }

                $sumCents = (int) round(($subtotal + $iva) * 100);
                $totalCents = (int) round($total * 100);
                if ($sumCents !== $totalCents) {
                    $errors[] = sprintf(
                        'Fila %d: subtotal+iva (%.2f) no es igual a total (%.2f).',
                        $rowNum,
                        $sumCents / 100,
                        $totalCents / 100
                    );

                    continue;
                }

                if ($ivaRate->rate() > 0) {
                    $ivaCents = (int) round($iva * 100);
                    $ivaStrictCents = (int) round($subtotal * $ivaRate->rate() * 100);
                    if (abs($ivaCents - $ivaStrictCents) > 1) {
                        $errors[] = sprintf(
                            'Fila %d: iva (%.2f) fuera de tolerancia ±0.01 vs subtotal × iva_rate (%.2f).',
                            $rowNum,
                            $ivaCents / 100,
                            $ivaStrictCents / 100
                        );

                        continue;
                    }
                }

                $email = mb_strtolower(trim((string) ($row['created_by_email'] ?? '')));
                $userId = $catalogs['users'][$email] ?? null;
                if ($userId === null) {
                    $errors[] = "Fila {$rowNum}: usuario no encontrado: \"{$email}\".";

                    continue;
                }
                $departmentId = $userDepartments[$userId] ?? null;
                if ($departmentId === null) {
                    $errors[] = "Fila {$rowNum}: usuario \"{$email}\" no tiene department_id asignado.";

                    continue;
                }

                $currencyName = mb_strtoupper(trim((string) ($row['currency'] ?? '')));
                if ($currencyName !== '') {
                    $currencyId = $catalogs['currencies'][$currencyName] ?? null;
                    if ($currencyId === null) {
                        $errors[] = "Fila {$rowNum}: currency \"{$currencyName}\" no encontrada.";

                        continue;
                    }
                } else {
                    $currencyId = $parent->currency_id;
                }

                $branchName = mb_strtoupper(trim((string) ($row['branch'] ?? '')));
                if ($branchName !== '') {
                    $branchId = $catalogs['branches'][$branchName] ?? null;
                    if ($branchId === null) {
                        $errors[] = "Fila {$rowNum}: branch \"{$branchName}\" no encontrada.";

                        continue;
                    }
                } else {
                    $branchId = $parent->branch_id;
                }

                $paymentProvisionDate = $this->parseDate($row['payment_provision_date'] ?? '');
                if ($paymentProvisionDate === null) {
                    $errors[] = "Fila {$rowNum}: payment_provision_date inválida: \"{$row['payment_provision_date']}\".";

                    continue;
                }

                $createdAt = $this->parseDate($row['created_at'] ?? '');

                $invoiceFolioNormalized = mb_strtoupper(trim((string) ($row['invoice_folio'] ?? '')));
                $rfcCleaned = preg_replace('/\s+/u', '', mb_strtoupper(trim((string) ($row['rfc'] ?? ''))));

                $fingerprint = $this->fingerprint(
                    (int) $parent->id,
                    $invoiceFolioNormalized,
                    $paymentProvisionDate->format('Y-m-d'),
                    $total
                );
                if (isset($existingFingerprints[$fingerprint])) {
                    $skippedDuplicates[] = "Fila {$rowNum}: duplicado existente (folio padre {$folioNum}, factura \"{$invoiceFolioNormalized}\", fecha {$paymentProvisionDate->format('Y-m-d')}, total ".number_format($total, 2).').';

                    continue;
                }

                $groupKey = $this->groupKey($parent);
                $alreadyPaid = $groupPaid[$groupKey] ?? 0.0;
                $batchSpend = $batchGroupSpend[$groupKey] ?? 0.0;
                $budget = $groupBudgets[$groupKey] ?? 0.0;
                $wouldBeSpent = $alreadyPaid + $batchSpend + $total;

                if ($wouldBeSpent > $budget + 0.01) {
                    $errors[] = sprintf(
                        'Fila %d: pago de $%.2f (folio padre %d) excede el saldo del grupo. Budget: $%.2f, ya pagado: $%.2f, en este lote: $%.2f, faltante tras carga: $%.2f.',
                        $rowNum,
                        $total,
                        $folioNum,
                        $budget,
                        $alreadyPaid,
                        $batchSpend,
                        $budget - $wouldBeSpent
                    );

                    continue;
                }

                $payment = new InvestmentPaymentRequest([
                    'provider' => $row['provider'] ?? '',
                    'rfc' => $rfcCleaned !== '' ? $rfcCleaned : null,
                    'invoice_folio' => $invoiceFolioNormalized !== '' ? $invoiceFolioNormalized : null,
                    'description' => $row['description'] ?? null,
                    'subtotal' => $subtotal,
                    'iva_rate' => $ivaRate,
                    'iva' => $iva,
                    'retention' => isset($row['retention']) ? (bool) (int) $row['retention'] : false,
                    'total' => $total,
                ]);

                $payment->investment_request_id = (int) $parent->id;
                $payment->user_id = (int) $userId;
                $payment->department_id = (int) $departmentId;
                $payment->currency_id = (int) $currencyId;
                $payment->branch_id = (int) $branchId;
                $payment->expense_concept_id = $parent->expense_concept_id;
                $payment->payment_type = $paymentType;
                $payment->status = 'approved';
                $payment->payment_provision_date = $paymentProvisionDate;
                $payment->payment_week_number = $paymentProvisionDate->weekOfYear;

                if ($createdAt !== null) {
                    $payment->timestamps = false;
                    $payment->created_at = $createdAt;
                    $payment->updated_at = $createdAt;
                }

                $payment->save();

                $approval = new InvestmentPaymentApproval;
                $approval->investment_payment_request_id = (int) $payment->id;
                $approval->user_id = (int) $authorizer->id;
                $approval->status = 'approved';
                $approval->responded_at = $createdAt ?? now();
                if ($createdAt !== null) {
                    $approval->timestamps = false;
                    $approval->created_at = $createdAt;
                    $approval->updated_at = $createdAt;
                }
                $approval->save();

                $batchGroupSpend[$groupKey] = $batchSpend + $total;
                $existingFingerprints[$fingerprint] = true;
                $imported++;

                $rowsToCreate[] = [
                    'row' => $rowNum,
                    'folio' => $folioNum,
                    'provider' => $payment->provider,
                    'total' => $total,
                    'group_key' => $groupKey,
                ];
            }

            if (! empty($errors)) {
                DB::rollBack();
                $this->command->error('CARGA ABORTADA: hay errores. Rollback ejecutado, no se escribió a BD.');
            } elseif ($isDryRun) {
                DB::rollBack();
                $this->command->info('DRY-RUN: rollback ejecutado, no se escribió a BD.');
            } else {
                DB::commit();
                $this->command->info('Commit ejecutado.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Excepción durante la carga, rollback ejecutado: '.$e->getMessage());

            throw $e;
        }

        $this->report($imported, $skippedDuplicates, $errors, $adjustments, $rowsToCreate, $batchGroupSpend, $groupBudgets, $groupPaid, $isDryRun);
    }

    /**
     * @return array{adjusted: bool, subtotal: float, iva: float, total: float}
     */
    private function applyCfdiRoundingCorrection(float $subtotal, float $rate, float $iva, float $total): array
    {
        if ($rate <= 0) {
            return ['adjusted' => false, 'subtotal' => $subtotal, 'iva' => $iva, 'total' => $total];
        }

        $totalCents = (int) round($total * 100);
        $centsFraction = $totalCents % 100;

        if (! in_array($centsFraction, self::ROUNDING_ARTIFACT_CENTS, true)) {
            return ['adjusted' => false, 'subtotal' => $subtotal, 'iva' => $iva, 'total' => $total];
        }

        $newTotal = round($total);
        $newSubtotal = round($newTotal / (1 + $rate), 2);
        $newIva = round($newTotal - $newSubtotal, 2);

        return [
            'adjusted' => true,
            'subtotal' => $newSubtotal,
            'iva' => $newIva,
            'total' => $newTotal,
        ];
    }

    /**
     * Build group budget map and parent IDs per group, considering only Completed parents.
     *
     * @param  Collection<int, InvestmentRequest>  $allParents
     * @return array{0: array<string, float>, 1: array<string, list<int>>}
     */
    private function buildGroupBudgets($allParents): array
    {
        $budgets = [];
        $parentIds = [];

        foreach ($allParents as $parent) {
            if ($parent->status::class !== Completed::class) {
                continue;
            }

            $key = $this->groupKey($parent);
            $budgets[$key] = ($budgets[$key] ?? 0.0) + (float) $parent->total;
            $parentIds[$key][] = (int) $parent->id;
        }

        return [$budgets, $parentIds];
    }

    /**
     * @param  array<string, list<int>>  $groupParentIds
     * @return array<string, float>
     */
    private function buildGroupPaid(array $groupParentIds): array
    {
        $paymentsByParent = InvestmentPaymentRequest::query()
            ->whereIn('status', ['pending_approval', 'approved'])
            ->select('investment_request_id', DB::raw('SUM(total) as paid'))
            ->groupBy('investment_request_id')
            ->pluck('paid', 'investment_request_id')
            ->all();

        $groupPaid = [];
        foreach ($groupParentIds as $key => $parentIds) {
            $sum = 0.0;
            foreach ($parentIds as $pid) {
                $sum += (float) ($paymentsByParent[$pid] ?? 0);
            }
            $groupPaid[$key] = $sum;
        }

        return $groupPaid;
    }

    /**
     * @return array<string, true>
     */
    private function buildExistingFingerprints(): array
    {
        $fingerprints = [];
        InvestmentPaymentRequest::query()
            ->select('investment_request_id', 'invoice_folio', 'payment_provision_date', 'total')
            ->get()
            ->each(function (InvestmentPaymentRequest $p) use (&$fingerprints): void {
                $fp = $this->fingerprint(
                    (int) $p->investment_request_id,
                    (string) ($p->invoice_folio ?? ''),
                    $p->payment_provision_date?->format('Y-m-d') ?? '',
                    (float) $p->total
                );
                $fingerprints[$fp] = true;
            });

        return $fingerprints;
    }

    private function groupKey(InvestmentRequest $parent): string
    {
        if (! $parent->project_id || ! $parent->investment_expense_concept_id) {
            return "solo:{$parent->id}";
        }

        return "{$parent->project_id}:{$parent->investment_expense_concept_id}";
    }

    private function fingerprint(int $investmentRequestId, string $invoiceFolio, string $date, float $total): string
    {
        return $investmentRequestId.'|'.$invoiceFolio.'|'.$date.'|'.number_format($total, 2, '.', '');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  Collection<string, int>  $collection
     * @return array<string, int>
     */
    private function indexByName($collection): array
    {
        return $collection
            ->mapWithKeys(fn (int $id, string $name): array => [mb_strtoupper(trim($name)) => $id])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, string>
     */
    private function normalizeKeys(array $record): array
    {
        $normalized = [];
        foreach ($record as $key => $value) {
            $normalized[mb_strtolower(trim((string) $key))] = is_string($value) ? $value : (string) $value;
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $skipped
     * @param  list<string>  $errors
     * @param  list<array{row: int, folio: int, original: array{subtotal: float, iva: float, total: float}, corrected: array{subtotal: float, iva: float, total: float}}>  $adjustments
     * @param  list<array{row: int, folio: int, provider: string, total: float, group_key: string}>  $rowsToCreate
     * @param  array<string, float>  $batchGroupSpend
     * @param  array<string, float>  $groupBudgets
     * @param  array<string, float>  $groupPaid
     */
    private function report(
        int $imported,
        array $skipped,
        array $errors,
        array $adjustments,
        array $rowsToCreate,
        array $batchGroupSpend,
        array $groupBudgets,
        array $groupPaid,
        bool $isDryRun
    ): void {
        $mode = $isDryRun ? 'DRY-RUN' : 'CARGA REAL';
        $writtenLabel = $isDryRun || ! empty($errors) ? 'simuladas (rollback)' : 'escritas a BD';

        $this->command->info('========================================');
        $this->command->info("=== Reporte InvestmentPaymentsHistorical [{$mode}] ===");
        $this->command->info('========================================');
        $this->command->info("Filas {$writtenLabel}: {$imported}");
        $this->command->info('Duplicados omitidos: '.count($skipped));
        $this->command->info('Errores: '.count($errors));
        $this->command->info('Ajustes CFDI por redondeo: '.count($adjustments));

        if (! empty($adjustments)) {
            $this->command->info("\n--- Ajustes CFDI por redondeo ---");
            foreach ($adjustments as $a) {
                $this->command->line(sprintf(
                    '  • Fila %d (folio %d): %.2f / %.2f / %.2f → %.2f / %.2f / %.2f',
                    $a['row'],
                    $a['folio'],
                    $a['original']['subtotal'],
                    $a['original']['iva'],
                    $a['original']['total'],
                    $a['corrected']['subtotal'],
                    $a['corrected']['iva'],
                    $a['corrected']['total']
                ));
            }
        }

        if (! empty($skipped)) {
            $this->command->warn("\n--- Duplicados omitidos ---");
            foreach ($skipped as $s) {
                $this->command->warn('  • '.$s);
            }
        }

        if (! empty($errors)) {
            $this->command->error("\n--- Errores ---");
            foreach ($errors as $e) {
                $this->command->error('  • '.$e);
            }
        }

        if (! empty($batchGroupSpend)) {
            $this->command->info("\n--- Saldos por grupo después de la carga ---");
            foreach ($batchGroupSpend as $key => $batchSpent) {
                $budget = $groupBudgets[$key] ?? 0.0;
                $alreadyPaid = $groupPaid[$key] ?? 0.0;
                $remainingAfter = $budget - $alreadyPaid - $batchSpent;
                $this->command->line(sprintf(
                    '  • Grupo %s | Budget: $%.2f | Pagado previo: $%.2f | Este lote: $%.2f | Saldo final: $%.2f',
                    $key,
                    $budget,
                    $alreadyPaid,
                    $batchSpent,
                    $remainingAfter
                ));
            }
        }

        if (! empty($rowsToCreate) && $imported > 0) {
            $totalAmount = array_sum(array_column($rowsToCreate, 'total'));
            $this->command->info("\n--- Resumen monto total ---");
            $this->command->line(sprintf('  Suma de totales en este lote: $%.2f', $totalAmount));
        }
    }
}
