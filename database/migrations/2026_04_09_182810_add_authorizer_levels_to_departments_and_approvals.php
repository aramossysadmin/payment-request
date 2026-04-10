<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('authorizer_level_1_id')->nullable()->after('description')->constrained('users')->restrictOnDelete();
            $table->foreignId('authorizer_level_2_id')->nullable()->after('authorizer_level_1_id')->constrained('users')->restrictOnDelete();
        });

        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(1)->after('stage');
        });

        // Migrate existing data from department_user pivot to new columns
        $pivotData = DB::table('department_user')
            ->select('department_id', 'user_id')
            ->orderBy('department_id')
            ->orderBy('id')
            ->get()
            ->groupBy('department_id');

        foreach ($pivotData as $departmentId => $users) {
            $level1UserId = $users->first()->user_id;
            $level2UserId = $users->count() > 1 ? $users->skip(1)->first()->user_id : null;

            DB::table('departments')
                ->where('id', $departmentId)
                ->update([
                    'authorizer_level_1_id' => $level1UserId,
                    'authorizer_level_2_id' => $level2UserId,
                ]);
        }

        // Assign level = 1 to all existing approval records
        DB::table('payment_request_approvals')->update(['level' => 1]);

        // Drop the old unique constraint and add new one without it
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->dropUnique(['payment_request_id', 'user_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_request_approvals', function (Blueprint $table) {
            $table->unique(['payment_request_id', 'user_id', 'stage']);
            $table->dropColumn('level');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('authorizer_level_2_id');
            $table->dropConstrainedForeignId('authorizer_level_1_id');
        });
    }
};
