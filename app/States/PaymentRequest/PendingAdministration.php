<?php

namespace App\States\PaymentRequest;

class PendingAdministration extends PaymentRequestState
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
