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
        Schema::table('failed_import_rows', function (Blueprint $table) {
            $table->dropColumn(['values', 'errors']);
            $table->json('data');
            $table->text('validation_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failed_import_rows', function (Blueprint $table) {
            $table->dropColumn(['data', 'validation_error']);
            $table->json('values');
            $table->json('errors');
        });
    }
};
