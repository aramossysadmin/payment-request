<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE "investment_requests_new" (
                "id" integer primary key autoincrement not null,
                "uuid" varchar not null unique,
                "folio_number" integer unique,
                "user_id" integer not null,
                "department_id" integer not null,
                "provider" varchar,
                "rfc" varchar(13),
                "invoice_folio" varchar(255),
                "currency_id" integer not null,
                "branch_id" integer not null,
                "expense_concept_id" integer,
                "description" text,
                "payment_type_id" integer,
                "advance_documents" text,
                "status" varchar not null default \'pending_department\',
                "subtotal" decimal(14,2) not null,
                "iva_rate" varchar(4) not null default \'0.16\',
                "iva" decimal(14,2) not null,
                "retention" boolean not null default 0,
                "total" decimal(14,2) not null,
                "number_purchase_invoices" integer,
                "number_vendor_payments" integer,
                "created_at" timestamp,
                "updated_at" timestamp,
                "deleted_at" timestamp,
                "project_id" integer,
                "investment_expense_concept_id" integer,
                "contact_name" varchar(255),
                "contact_email" varchar(255),
                "contact_phone" varchar(20),
                "is_addendum" boolean not null default 0
            )
        ');

        DB::statement('INSERT INTO "investment_requests_new" SELECT * FROM "investment_requests"');
        DB::statement('DROP TABLE "investment_requests"');
        DB::statement('ALTER TABLE "investment_requests_new" RENAME TO "investment_requests"');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('
            CREATE TABLE "investment_requests_old" (
                "id" integer primary key autoincrement not null,
                "uuid" varchar not null unique,
                "folio_number" integer unique,
                "user_id" integer not null,
                "department_id" integer not null,
                "provider" varchar not null,
                "rfc" varchar(13),
                "invoice_folio" varchar(255),
                "currency_id" integer not null,
                "branch_id" integer not null,
                "expense_concept_id" integer,
                "description" text,
                "payment_type_id" integer,
                "advance_documents" text,
                "status" varchar not null default \'pending_department\',
                "subtotal" decimal(14,2) not null,
                "iva_rate" varchar(4) not null default \'0.16\',
                "iva" decimal(14,2) not null,
                "retention" boolean not null default 0,
                "total" decimal(14,2) not null,
                "number_purchase_invoices" integer,
                "number_vendor_payments" integer,
                "created_at" timestamp,
                "updated_at" timestamp,
                "deleted_at" timestamp,
                "project_id" integer,
                "investment_expense_concept_id" integer,
                "contact_name" varchar(255),
                "contact_email" varchar(255),
                "contact_phone" varchar(20),
                "is_addendum" boolean not null default 0
            )
        ');

        DB::statement('INSERT INTO "investment_requests_old" SELECT * FROM "investment_requests"');
        DB::statement('DROP TABLE "investment_requests"');
        DB::statement('ALTER TABLE "investment_requests_old" RENAME TO "investment_requests"');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
