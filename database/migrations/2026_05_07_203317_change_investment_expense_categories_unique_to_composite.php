<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investment_expense_categories', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->unique(['name', 'super_category']);
        });
    }

    public function down(): void
    {
        Schema::table('investment_expense_categories', function (Blueprint $table): void {
            $table->dropUnique(['name', 'super_category']);
            $table->unique(['name']);
        });
    }
};
