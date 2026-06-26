<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE clearance_actions MODIFY action_type VARCHAR(20) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clearance_actions MODIFY action_type ENUM('sell', 'donate', 'dispose') NOT NULL");
    }
};
