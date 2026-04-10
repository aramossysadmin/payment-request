<?php

namespace App\Notifications\Concerns;

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
     * @param  \App\Models\PaymentRequest|\App\Models\InvestmentRequest  $request
     */
    private function appendStageInfo(MailMessage $mail, mixed $request): MailMessage
    {
        $latestApproval = $request->approvals()
            ->latest()
            ->first();

        if ($latestApproval) {
            $mail->line('**Etapa actual:** '.$this->stageLabel($latestApproval->stage).' - Nivel '.$latestApproval->level);
        }

        $mail->line('**Departamento:** '.($request->department->name ?? '-'));

        return $mail;
    }

    /**
     * @param  \App\Models\PaymentRequest|\App\Models\InvestmentRequest  $request
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
