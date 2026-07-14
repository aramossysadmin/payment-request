<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Department;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Capturado en beforeSave para preservar el viejo principal como adicional
     * si el principal cambia (decisión #11 del plan multi-departamento).
     */
    protected ?int $oldPrincipalId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Antes de guardar: validar que NO se quiten departamentos donde el user
     * tenga registros activos (solicitudes / pagos / batches en proceso).
     *
     * También captura el principal viejo para preservarlo como adicional si cambia.
     */
    protected function beforeSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        // Capturar viejo principal antes de que el save lo sobrescriba
        $this->oldPrincipalId = $user->department_id ? (int) $user->department_id : null;

        $additionalIds = collect($this->form->getRawState()['additional_departments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        $newPrincipalId = $this->data['department_id'] ?? null;

        // Si el principal cambió, el viejo principal queda como adicional (decisión #11).
        // Por lo tanto NO se considera "removido".
        if ($this->oldPrincipalId && $newPrincipalId && $this->oldPrincipalId !== (int) $newPrincipalId) {
            if (! in_array($this->oldPrincipalId, $additionalIds, true)) {
                $additionalIds[] = $this->oldPrincipalId;
            }
        }

        $newAllIds = array_values(array_unique(array_filter(array_merge(
            $newPrincipalId ? [(int) $newPrincipalId] : [],
            $additionalIds,
        ))));

        $currentAllIds = $user->departments()->pluck('departments.id')->all();

        $departmentIdsBeingRemoved = array_diff($currentAllIds, $newAllIds);

        if (empty($departmentIdsBeingRemoved)) {
            return;
        }

        $messages = [];

        foreach ($departmentIdsBeingRemoved as $deptId) {
            $deptName = Department::find($deptId)?->name ?? "ID {$deptId}";

            $activeInvestmentRequests = InvestmentRequest::query()
                ->where('user_id', $user->id)
                ->where('department_id', $deptId)
                ->where('status', '!=', 'completed')
                ->count();

            $activePaymentRequests = InvestmentPaymentRequest::query()
                ->where('user_id', $user->id)
                ->where('department_id', $deptId)
                ->whereNotIn('status', ['completed', 'auto_cancelled', 'final_rejected', 'rejected'])
                ->count();

            $activeBatches = InvestmentPaymentBatch::query()
                ->where('user_id', $user->id)
                ->where('department_id', $deptId)
                ->whereNotIn('status', ['completed', 'final_approved', 'final_rejected', 'projectmanager_rejected', 'ceo_rejected'])
                ->count();

            $total = $activeInvestmentRequests + $activePaymentRequests + $activeBatches;

            if ($total > 0) {
                $detail = [];
                if ($activeInvestmentRequests > 0) {
                    $detail[] = "{$activeInvestmentRequests} Solicitud(es) de Inversión pendiente(s)";
                }
                if ($activePaymentRequests > 0) {
                    $detail[] = "{$activePaymentRequests} Pago(s) individual(es) en proceso";
                }
                if ($activeBatches > 0) {
                    $detail[] = "{$activeBatches} Batch(es) activo(s)";
                }

                $messages[] = "  • {$deptName}: ".implode(', ', $detail);
            }
        }

        if (! empty($messages)) {
            $body = "No es posible quitar departamentos del usuario {$user->name} porque tiene pendientes:\n\n"
                .implode("\n", $messages)
                ."\n\nResuelve estos pendientes (completar, rechazar o cancelar) antes de quitar los departamentos.";

            Notification::make()
                ->title('No se puede guardar')
                ->body($body)
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * Sincroniza la pivot tras guardar el principal. Aplica la regla:
     * - Si cambió el principal, el viejo queda como adicional (decisión #11).
     */
    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $additionalIds = collect($this->form->getRawState()['additional_departments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        $newPrincipalId = $user->department_id ? (int) $user->department_id : null;

        // Si el principal cambió, agregar el viejo como adicional (decisión #11).
        if ($this->oldPrincipalId && $newPrincipalId && $this->oldPrincipalId !== $newPrincipalId) {
            if (! in_array($this->oldPrincipalId, $additionalIds, true)) {
                $additionalIds[] = $this->oldPrincipalId;
            }
        }

        $principalArr = $newPrincipalId ? [$newPrincipalId] : [];
        $allIds = array_values(array_unique(array_merge($principalArr, $additionalIds)));

        $user->departments()->sync($allIds);
    }
}
