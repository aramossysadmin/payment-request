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
     * @param  PaymentRequest|InvestmentRequest  $request
     */
    private function appendStageInfo(MailMessage $mail, mixed $request): MailMessage
    {
        $mail->line('**Departamento:** '.($request->department->name ?? '-'));

        $latestApproval = $request->approvals()
            ->latest()
            ->first();

        if ($latestApproval && ! $request instanceof InvestmentRequest) {
            $mail->line('**Etapa actual:** '.$this->stageLabel($latestApproval->stage).' - Nivel '.$latestApproval->level);
        }

        return $mail;
    }

    /**
     * @param  PaymentRequest|InvestmentRequest  $request
     */
    private function appendDocumentLinks(MailMessage $mail, mixed $request): MailMessage
    {
        $documents = $request->advance_documents;

        if (! is_array($documents) || count($documents) === 0) {
            return $mail;
        }

        $validDocs = array_filter($documents, fn ($doc) => is_string($doc) && $doc !== '');

        if (count($validDocs) === 0) {
            return $mail;
        }

        $mail->line('---');
        $mail->line('**Documentos adjuntos:**');

        foreach ($validDocs as $doc) {
            $filename = basename($doc);
            $signedUrl = URL::temporarySignedRoute(
                'documents.view',
                now()->addHours(48),
                ['path' => $doc],
            );
            $mail->line("[{$filename}]({$signedUrl})");
        }

        return $mail;
    }
}
