<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->index()->unique();
            $table->uuid('issued_by_user_did')->index();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedBigInteger('current_usage_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->index()->default('active');
            $table->timestamps();

            $table->foreign('issued_by_user_did')->references('did')->on('users')->onDelete('cascade');
        });

        Schema::create('invitation_code_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invitation_code_id')->index();
            $table->uuid('used_by_user_did')->index();
            $table->timestamps();

            $table->foreign('invitation_code_id')->references('id')->on('invitation_codes')->onDelete('cascade');
            $table->foreign('used_by_user_did')->references('did')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_code_usages');
        Schema::dropIfExists('invitation_codes');
    }
};
