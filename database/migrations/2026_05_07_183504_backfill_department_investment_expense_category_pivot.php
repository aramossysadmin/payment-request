<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('investment_expense_categories')
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->select(['id', 'department_id'])
            ->chunkById(500, function ($categories): void {
                $rows = $categories
                    ->map(fn ($category) => [
                        'department_id' => $category->department_id,
                        'investment_expense_category_id' => $category->id,
                    ])
                    ->all();

                if (! empty($rows)) {
                    DB::table('department_investment_expense_category')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Backfilled pivot rows are intentionally left in place on rollback.
     * Per project policy, records must never be deleted automatically.
     * To revert this migration, restore the database from backup.
     */
    public function down(): void
    {
        //
    }
};
