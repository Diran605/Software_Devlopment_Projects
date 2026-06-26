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
        Schema::table('imports', function (Blueprint $table) {
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->timestamp('completed_at')->nullable()->after('updated_at');
        });

        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->onDelete('cascade');
            $table->integer('row_number');
            $table->json('values');
            $table->json('errors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
        
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn(['processed_rows', 'completed_at']);
        });
    }
};
