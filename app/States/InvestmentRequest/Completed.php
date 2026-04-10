<?php

namespace App\States\InvestmentRequest;

class Completed extends InvestmentRequestState
{
    public static string $name = 'completed';

    public function label(): string
    {
        return 'Finalizado';
    }

    public function color(): string
    {
        return 'success';
    }
}
