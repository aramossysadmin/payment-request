<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_expense_concepts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('investment_expense_category_id')->constrained();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_expense_concepts');
    }
};
