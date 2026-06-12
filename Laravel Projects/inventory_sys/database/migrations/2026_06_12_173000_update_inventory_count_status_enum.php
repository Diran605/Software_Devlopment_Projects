<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update the status enum to include in_progress and pending_approval
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE inventory_counts MODIFY COLUMN status ENUM(
            'draft', 'in_progress', 'pending_approval', 'approved', 'posted', 'cancelled'
        ) DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Revert to original enum
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE inventory_counts MODIFY COLUMN status ENUM(
            'draft', 'submitted', 'approved', 'posted', 'cancelled'
        ) DEFAULT 'draft'");
    }
};
