<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'PESO MEXICANO', 'prefix' => 'MXN'],
            ['name' => 'DÓLAR ESTADOUNIDENSE', 'prefix' => 'USD'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['prefix' => $currency['prefix']], $currency);
        }
    }
}
