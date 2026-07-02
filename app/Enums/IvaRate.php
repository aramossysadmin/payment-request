<?php

namespace App\Enums;

enum IvaRate: string
{
    case Zero = '0.00';
    case Four = '0.04';
    case Eight = '0.08';
    case Ten = '0.10';
    case Sixteen = '0.16';
    case TwentyOne = '0.21';

    public function label(): string
    {
        return match ($this) {
            self::Zero => 'IVA 0%',
            self::Four => 'IVA 4%',
            self::Eight => 'IVA 8%',
            self::Ten => 'IVA 10%',
            self::Sixteen => 'IVA 16%',
            self::TwentyOne => 'IVA 21%',
        };
    }

    public function rate(): float
    {
        return (float) $this->value;
    }
}
