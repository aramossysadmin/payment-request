<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Society;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            'CERRANDO LA PINZA' => ['CERRANDO LA PINZA'],
            'CUATRO CUARTOS INMOBILIARIA' => ['CUATRO CUARTOS INMOBILIARIA'],
            'FUSION SONORENSE' => ['MOCHOMOS MONTERREY'],
            'GASTRONOMIA COSMOS' => ['MOCHOMOS MITIKAH', 'RYOSHI QUERETARO'],
            'GRUPO COSTENO' => ['TIGRE MADRID'],
            'JAPAN BAR' => [
                'HOTARU MITIKAH',
                'ICHIKANI CANCUN',
                'MOCHOMOS CANCUN',
                'PUERTO TIGRE MONTERREY',
                'RYOSHI CANCUN',
                'RYOSHI MASARYK',
            ],
            'KING KANGREJO' => ['KING KANGREJO'],
            'MADMEX PROPIERTIES' => [
                'AMATERASU MADRID',
                'HOTARU MADRID',
                'ICHIKANI MADRID',
            ],
            'MERCADO DE EXPERIENCIAS' => [
                'BOTICA',
                'HOTARU ARCOS CDMX',
                'ICHIKANI ARCOS',
                'MOCHOMOS ARCOS',
                'TIGRE MASARYK',
            ],
            'MOVIMIENTO INMOBIL' => ['MOVIMIENTO INMOBIL'],
            'OPERACION COSTENO' => ['OPERACION COSTENO'],
            'OPERACION TIMON' => ['OPERACION TIMON'],
            'PASION SONORENSE' => ['MOCHOMOS PUEBLA'],
            'SASTRERIA VAN DER WOLF' => [
                'AMATERASU SKY BAR',
                'COMEDOR METROPOLITAN',
                'HOTARU CDMX',
                'HOTARU MONTERREY',
                'HOTARU PUEBLA',
                'ICHIKANI ARTZ',
                'ICHIKANI LOMAS',
                'ICHIKANI METROPOLITAN',
                'ICHIKANI MONTERREY',
                'RYOSHI MONTERREY',
                'RYOSHI PUEBLA',
                'UNA BURGER METROPOLITAN',
            ],
            'SELA HOUSING' => ['SELA HOUSING'],
            'STANDARD FOODS' => ['STANDARD FOODS'],
            'WILD LUXURY' => ['WILD LUXURY'],
            'YUMO CS' => ['LAZARO Y DIEGO'],
        ];

        foreach ($branches as $societyName => $branchNames) {
            $society = Society::where('name', $societyName)->first();

            if (! $society) {
                continue;
            }

            foreach ($branchNames as $branchName) {
                Branch::firstOrCreate(
                    ['name' => $branchName, 'society_id' => $society->id],
                );
            }
        }
    }
}
