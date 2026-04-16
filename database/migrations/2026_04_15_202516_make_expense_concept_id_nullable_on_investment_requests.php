<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $columns = collect(DB::select("PRAGMA table_info('investment_requests')"))
            ->pluck('name')
            ->implode(', ');

        DB::statement('ALTER TABLE investment_requests RENAME TO investment_requests_backup');

        DB::statement("
            CREATE TABLE investment_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR NOT NULL,
                folio_number INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                department_id INTEGER NOT NULL,
                provider VARCHAR NOT NULL,
                rfc VARCHAR(13),
                invoice_folio VARCHAR(255),
                currency_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                expense_concept_id INTEGER,
                description TEXT,
                payment_type_id INTEGER,
                advance_documents TEXT,
                status VARCHAR NOT NULL DEFAULT 'pending_department',
                subtotal DECIMAL(14,2) NOT NULL,
                iva_rate VARCHAR(4) NOT NULL,
                iva DECIMAL(14,2) NOT NULL,
                retention BOOLEAN NOT NULL DEFAULT 0,
                total DECIMAL(14,2) NOT NULL,
                number_purchase_invoices INTEGER,
                number_vendor_payments INTEGER,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                deleted_at TIMESTAMP,
                project_id INTEGER,
                investment_expense_concept_id INTEGER,
                contact_name VARCHAR(255),
                contact_email VARCHAR(255),
                contact_phone VARCHAR(20),
                is_addendum BOOLEAN NOT NULL DEFAULT 0,
                UNIQUE(uuid),
                UNIQUE(folio_number)
            )
        ");

        DB::statement("INSERT INTO investment_requests ({$columns}) SELECT {$columns} FROM investment_requests_backup");
        DB::statement('DROP TABLE investment_requests_backup');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Reverse not needed
    }
};
