<?php

namespace Database\Seeders;

use App\Models\Society;
use Illuminate\Database\Seeder;

class SocietySeeder extends Seeder
{
    public function run(): void
    {
        $societies = [
            'CERRANDO LA PINZA',
            'CUATRO CUARTOS INMOBILIARIA',
            'FUSION SONORENSE',
            'GASTRONOMIA COSMOS',
            'GRUPO COSTENO',
            'JAPAN BAR',
            'KING KANGREJO',
            'MADMEX PROPIERTIES',
            'MERCADO DE EXPERIENCIAS',
            'MOVIMIENTO INMOBIL',
            'OPERACION COSTENO',
            'OPERACION TIMON',
            'PASION SONORENSE',
            'SASTRERIA VAN DER WOLF',
            'SELA HOUSING',
            'STANDARD FOODS',
            'WILD LUXURY',
            'YUMO CS',
        ];

        foreach ($societies as $name) {
            Society::firstOrCreate(['name' => $name]);
        }
    }
}
