<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->string('invoice_documents_mode')->default('disabled')->after('requires_invoice_documents');
            $table->string('additional_documents_mode')->default('optional')->after('invoice_documents_mode');
        });

        // Migrate existing data: requires_invoice_documents=true → invoice_documents_mode=required
        DB::table('payment_types')
            ->where('requires_invoice_documents', true)
            ->update(['invoice_documents_mode' => 'required']);

        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn('requires_invoice_documents');
        });
    }

    public function down(): void
    {
        Schema::table('payment_types', function (Blueprint $table) {
            $table->boolean('requires_invoice_documents')->default(false)->after('slug');
        });

        DB::table('payment_types')
            ->where('invoice_documents_mode', 'required')
            ->update(['requires_invoice_documents' => true]);

        Schema::table('payment_types', function (Blueprint $table) {
            $table->dropColumn(['invoice_documents_mode', 'additional_documents_mode']);
        });
    }
};
