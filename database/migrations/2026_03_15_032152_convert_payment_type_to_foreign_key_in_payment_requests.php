<?php

use App\Models\PaymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $invoice = PaymentType::firstOrCreate(
            ['slug' => 'invoice'],
            ['name' => 'PAGO CON FACTURA', 'requires_invoice_documents' => true, 'is_active' => true]
        );

        $advance = PaymentType::firstOrCreate(
            ['slug' => 'advance'],
            ['name' => 'ANTICIPO', 'requires_invoice_documents' => false, 'is_active' => true]
        );

        $investment = PaymentType::firstOrCreate(
            ['slug' => 'investment'],
            ['name' => 'INVERSIONES', 'requires_invoice_documents' => false, 'is_active' => true]
        );

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreignId('payment_type_id')->nullable()->after('payment_type')->constrained('payment_types')->restrictOnDelete();
        });

        $mapping = [
            'invoice' => $invoice->id,
            'advance' => $advance->id,
            'investment' => $investment->id,
        ];

        foreach ($mapping as $slug => $id) {
            DB::table('payment_requests')
                ->where('payment_type', $slug)
                ->update(['payment_type_id' => $id]);
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('payment_type')->default('invoice')->after('description');
        });

        $types = PaymentType::all();

        foreach ($types as $type) {
            DB::table('payment_requests')
                ->where('payment_type_id', $type->id)
                ->update(['payment_type' => $type->slug]);
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_type_id']);
            $table->dropColumn('payment_type_id');
        });
    }
};
