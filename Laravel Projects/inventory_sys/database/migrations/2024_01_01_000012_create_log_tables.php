<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deleted_by')->constrained('users')->restrictOnDelete();
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');
            $table->string('record_number');
            $table->text('reason');
            $table->json('snapshot');
            $table->json('stock_reversal');
            $table->timestamp('deleted_at');
            $table->index(['branch_id', 'record_type', 'deleted_at']);
            $table->index(['record_type', 'record_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event', ['created', 'updated', 'deleted', 'approved', 'rejected', 'posted', 'cancelled', 'received', 'login', 'logout']);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'event', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('deletion_logs');
    }
};
