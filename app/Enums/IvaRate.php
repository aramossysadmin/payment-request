<?php

namespace App\Enums;

enum IvaRate: string
{
    case Zero = '0.00';
    case Eight = '0.08';
    case Sixteen = '0.16';

    public function label(): string
    {
        return match ($this) {
            self::Zero => 'IVA 0%',
            self::Eight => 'IVA 8%',
            self::Sixteen => 'IVA 16%',
        };
    }

    public function rate(): float
    {
        return (float) $this->value;
    }
}
