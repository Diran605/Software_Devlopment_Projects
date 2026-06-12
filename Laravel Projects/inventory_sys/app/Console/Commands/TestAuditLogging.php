<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\DeletionLog;
use Illuminate\Console\Command;

class TestAuditLogging extends Command
{
    protected $signature = 'audit:test';
    protected $description = 'Test audit and deletion logging';

    public function handle(): void
    {
        $this->info('Testing audit logging...');

        $initialAuditCount = AuditLog::count();
        $initialDeletionCount = DeletionLog::count();

        $this->info("Initial AuditLog count: $initialAuditCount");
        $this->info("Initial DeletionLog count: $initialDeletionCount");

        // Authenticate as a user
        $user = User::first();
        if ($user) {
            auth()->loginUsingId($user->id);
            $this->info("Authenticated as: {$user->name}");
        }

        // Find a branch to test with
        $branch = Branch::first();

        if (!$branch) {
            $this->error('No branches found. Create a branch first.');
            return;
        }

        // Test update (should create an audit log)
        $this->info("Updating branch: {$branch->name}");
        $originalName = $branch->name;
        $branch->update(['name' => $branch->name . ' (Updated at ' . now()->format('H:i:s') . ')']);

        $newAuditCount = AuditLog::count();
        $this->info("AuditLog count after update: $newAuditCount (added: " . ($newAuditCount - $initialAuditCount) . ")");

        // Show the latest audit log
        $latestAudit = AuditLog::latest()->first();
        if ($latestAudit) {
            $this->line("✓ Latest Audit Log:");
            $this->line("  Event: {$latestAudit->event}");
            $this->line("  Type: {$latestAudit->auditable_type}");
            $this->line("  User: {$latestAudit->user_id}");
        } else {
            $this->error('✗ No audit logs found - listener may not be working!');
        }

        // Restore original name
        $branch->update(['name' => $originalName]);

        $this->info('✓ Audit logging test complete');
    }
}
