<?php

namespace App\States\InvestmentRequest;

class PendingTreasury extends InvestmentRequestState
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
