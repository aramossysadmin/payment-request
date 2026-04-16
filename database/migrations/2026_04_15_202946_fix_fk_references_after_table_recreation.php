<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $this->recreateTable('investment_request_approvals');
        $this->recreateTable('investment_payment_requests');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // No reverse needed
    }

    private function recreateTable(string $table): void
    {
        $schema = DB::select('SELECT sql FROM sqlite_master WHERE name=?', [$table])[0]->sql;

        if (! str_contains($schema, 'investment_requests_backup')) {
            return;
        }

        $fixedSchema = str_replace('investment_requests_backup', 'investment_requests', $schema);
        $fixedSchema = str_replace("CREATE TABLE \"{$table}\"", "CREATE TABLE \"{$table}_fixed\"", $fixedSchema);

        $columns = collect(DB::select("PRAGMA table_info('{$table}')"))
            ->pluck('name')
            ->implode(', ');

        DB::statement($fixedSchema);
        DB::statement("INSERT INTO \"{$table}_fixed\" ({$columns}) SELECT {$columns} FROM \"{$table}\"");
        DB::statement("DROP TABLE \"{$table}\"");
        DB::statement("ALTER TABLE \"{$table}_fixed\" RENAME TO \"{$table}\"");
    }
};
