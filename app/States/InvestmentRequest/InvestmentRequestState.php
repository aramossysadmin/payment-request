<?php

namespace App\States\InvestmentRequest;

use Spatie\ModelStates\Attributes\AllowTransition;
use Spatie\ModelStates\Attributes\DefaultState;
use Spatie\ModelStates\State;

#[
    DefaultState(PendingDepartment::class),
    AllowTransition(PendingDepartment::class, Completed::class),
]
abstract class InvestmentRequestState extends State
{
    abstract public function label(): string;

    abstract public function color(): string;
}
