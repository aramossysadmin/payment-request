<?php

namespace App\States\InvestmentRequest;

class PendingAdministration extends InvestmentRequestState
{
    public static string $name = 'pending_administration';

    public function label(): string
    {
        return 'Pendiente Administración';
    }

    public function color(): string
    {
        return 'info';
    }
}
