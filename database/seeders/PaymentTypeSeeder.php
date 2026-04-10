<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'PAGO CON FACTURA', 'slug' => 'invoice', 'invoice_documents_mode' => 'required', 'additional_documents_mode' => 'optional', 'category' => 'payment'],
            ['name' => 'ANTICIPO', 'slug' => 'advance', 'invoice_documents_mode' => 'disabled', 'additional_documents_mode' => 'optional', 'category' => 'payment'],
            ['name' => 'INVERSIONES', 'slug' => 'investment', 'invoice_documents_mode' => 'disabled', 'additional_documents_mode' => 'optional', 'category' => 'investment'],
        ];

        foreach ($types as $type) {
            PaymentType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
