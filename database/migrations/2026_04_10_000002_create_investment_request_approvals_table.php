<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('stage')->default('department');
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('approval_token')->nullable()->unique();
            $table->timestamp('approval_token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_request_approvals');
    }
};
