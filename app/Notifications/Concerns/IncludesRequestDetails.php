<?php

namespace App\Notifications\Concerns;

use App\Models\InvestmentRequest;
use App\Models\PaymentRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

trait IncludesRequestDetails
{
    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'department' => 'Departamento',
            'administration' => 'Administración',
            'treasury' => 'Tesorería',
            default => $stage,
        };
    }

    /**
     * Build stage info array for the email template.
     *
     * @param  PaymentRequest|InvestmentRequest  $request
     * @return array{department: string, stage: string|null}
     */
    private function getStageInfo(mixed $request): array
    {
        $info = [
            'department' => $request->department->name ?? '-',
            'stage' => null,
        ];

        $latestApproval = $request->approvals()
            ->latest()
            ->first();

        if ($latestApproval && ! $request instanceof InvestmentRequest) {
            $info['stage'] = $this->stageLabel($latestApproval->stage).' - Nivel '.$latestApproval->level;
        }

        return $info;
    }

    /**
     * Build documents array for the email template.
     *
     * @param  PaymentRequest|InvestmentRequest  $request
     * @return array<int, array{name: string, url: string}>
     */
    private function getDocuments(mixed $request): array
    {
        $documents = $request->advance_documents;

        if (! is_array($documents) || count($documents) === 0) {
            return [];
        }

        $validDocs = array_filter($documents, fn ($doc) => is_string($doc) && $doc !== '');

        if (count($validDocs) === 0) {
            return [];
        }

        $result = [];

        foreach ($validDocs as $doc) {
            $result[] = [
                'name' => basename($doc),
                'url' => URL::temporarySignedRoute(
                    'documents.view',
                    now()->addHours(48),
                    ['path' => $doc],
                ),
            ];
        }

        return $result;
    }

    /**
     * Build common request details for the email template.
     *
     * @param  PaymentRequest|InvestmentRequest  $request
     * @return array<int, array{label: string, value: string}>
     */
    private function getFullDetails(mixed $request): array
    {
        $isInvestment = $request instanceof InvestmentRequest;

        $conceptName = $isInvestment
            ? ($request->investmentExpenseConcept?->name ?? $request->expenseConcept?->name ?? '-')
            : ($request->expenseConcept?->name ?? '-');

        $details = [
            ['label' => 'Solicitante', 'value' => $request->user->name ?? '-'],
            ['label' => 'Sucursal', 'value' => $request->branch->name ?? '-'],
            ['label' => 'Concepto de Gasto', 'value' => $conceptName],
        ];

        if ($request->description) {
            $details[] = ['label' => 'Descripción', 'value' => $request->description];
        }

        if (! $isInvestment) {
            $details[] = ['label' => 'Tipo de Pago', 'value' => $request->paymentType->name ?? '-'];
        }

        return [
            ...$details,
            ['label' => 'Proveedor', 'value' => $request->provider],
            ['label' => 'Folio', 'value' => $request->invoice_folio ?? '-'],
            ['label' => 'Total', 'value' => '$ '.number_format($request->total, 2).' '.($request->currency->prefix ?? 'MXN')],
        ];
    }

    /**
     * Build minimal request details (for rejection/completion notifications).
     *
     * @param  PaymentRequest|InvestmentRequest  $request
     * @return array<int, array{label: string, value: string}>
     */
    private function getMinimalDetails(mixed $request): array
    {
        return [
            ['label' => 'Proveedor', 'value' => $request->provider],
            ['label' => 'Total', 'value' => '$ '.number_format($request->total, 2).' '.($request->currency->prefix ?? 'MXN')],
        ];
    }

    /**
     * Build the MailMessage using the shared template.
     *
     * @param  array<string, mixed>  $data
     */
    private function buildMailMessage(string $subject, array $data): MailMessage
    {
        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.request-notification', $data);
    }
}
