<?php

namespace App\States\InvestmentRequest;

class Rejected extends InvestmentRequestState
{
    public static string $name = 'rejected';

    public function label(): string
    {
        return 'Rechazado';
    }

    public function color(): string
    {
        return 'danger';
    }
}
