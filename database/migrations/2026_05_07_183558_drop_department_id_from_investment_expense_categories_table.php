<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $expectedAssociations = DB::table('investment_expense_categories')
            ->whereNotNull('department_id')
            ->count();

        $pivotAssociations = DB::table('department_investment_expense_category')
            ->whereIn(
                'investment_expense_category_id',
                DB::table('investment_expense_categories')
                    ->whereNotNull('department_id')
                    ->pluck('id')
                    ->all()
            )
            ->whereColumn(
                'department_investment_expense_category.department_id',
                DB::raw('(select department_id from investment_expense_categories where investment_expense_categories.id = department_investment_expense_category.investment_expense_category_id)')
            )
            ->count();

        if ($expectedAssociations !== $pivotAssociations) {
            throw new RuntimeException(sprintf(
                'Aborting: backfill mismatch. investment_expense_categories with department_id=%d, pivot rows matching=%d. Run the backfill migration before dropping the column.',
                $expectedAssociations,
                $pivotAssociations
            ));
        }

        Schema::table('investment_expense_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('investment_expense_categories', function (Blueprint $table): void {
            $table->foreignId('department_id')
                ->nullable()
                ->after('name')
                ->constrained()
                ->nullOnDelete();
        });
    }
};
