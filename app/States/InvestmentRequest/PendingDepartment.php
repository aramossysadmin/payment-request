<?php

namespace App\States\InvestmentRequest;

class PendingDepartment extends InvestmentRequestState
{
    public static string $name = 'pending_department';

    public function label(): string
    {
        return 'Pendiente Departamento';
    }

    public function color(): string
    {
        return 'warning';
    }
}
