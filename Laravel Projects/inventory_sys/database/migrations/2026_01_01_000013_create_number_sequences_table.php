<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix'); // PO, GRN, SO, TRF, OSE
            $table->string('branch_code');
            $table->string('date'); // Ymd
            $table->unsignedInteger('sequence');
            $table->timestamps();
            $table->unique(['prefix', 'branch_code', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
