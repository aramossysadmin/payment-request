<?php

namespace App\States\PaymentRequest;

class PendingDepartment extends PaymentRequestState
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
