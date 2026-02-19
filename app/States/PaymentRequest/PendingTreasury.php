<?php

namespace App\States\PaymentRequest;

class PendingTreasury extends PaymentRequestState
{
    public static string $name = 'pending_treasury';

    public function label(): string
    {
        return 'Pendiente Tesorería';
    }

    public function color(): string
    {
        return 'purple';
    }
}
