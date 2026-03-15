<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'PAGO CON FACTURA', 'slug' => 'invoice', 'requires_invoice_documents' => true],
            ['name' => 'ANTICIPO', 'slug' => 'advance', 'requires_invoice_documents' => false],
            ['name' => 'INVERSIONES', 'slug' => 'investment', 'requires_invoice_documents' => false],
        ];

        foreach ($types as $type) {
            PaymentType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
