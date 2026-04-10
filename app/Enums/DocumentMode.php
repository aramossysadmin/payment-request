<?php

namespace App\Enums;

enum DocumentMode: string
{
    case Disabled = 'disabled';
    case Optional = 'optional';
    case Required = 'required';

    public function label(): string
    {
        return match ($this) {
            self::Disabled => 'Deshabilitado',
            self::Optional => 'Opcional',
            self::Required => 'Obligatorio',
        };
    }
}
