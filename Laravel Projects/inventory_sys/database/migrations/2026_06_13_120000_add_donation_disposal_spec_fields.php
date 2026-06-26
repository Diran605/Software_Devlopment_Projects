<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('recipient_contact')->nullable()->after('recipient');
            $table->text('recipient_address')->nullable()->after('recipient_contact');
        });

        Schema::table('disposals', function (Blueprint $table) {
            $table->string('disposal_method')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['recipient_contact', 'recipient_address']);
        });

        Schema::table('disposals', function (Blueprint $table) {
            $table->dropColumn('disposal_method');
        });
    }
};
