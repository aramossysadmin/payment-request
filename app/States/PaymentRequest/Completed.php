<?php

namespace App\States\PaymentRequest;

class Completed extends PaymentRequestState
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
