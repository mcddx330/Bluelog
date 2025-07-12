<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 16)->index()->unique();
            $table->foreignUuid('issued_by_user_did')->constrained('users', 'did')->cascadeOnDelete();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedBigInteger('current_usage_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->index()->default('active');
            $table->timestamps();
        });

        Schema::create('invitation_code_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invitation_code_id')->index();
            $table->foreignUuid('used_by_user_did')->constrained('users', 'did')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('invitation_code_id')->references('id')->on('invitation_codes')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('invitation_code_usages');
        Schema::dropIfExists('invitation_codes');
    }
};
