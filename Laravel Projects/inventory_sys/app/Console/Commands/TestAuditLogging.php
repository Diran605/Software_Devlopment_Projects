<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeletionLog;
use App\Models\User;
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

        $user = User::first();
        if ($user) {
            auth()->loginUsingId($user->id);
            $this->info("Authenticated as: {$user->name}");
        }

        $branch = Branch::first();

        if (! $branch) {
            $this->error('No branches found. Create a branch first.');

            return;
        }

        $this->info("Updating branch address: {$branch->name}");
        $originalAddress = $branch->address;
        $branch->update(['address' => trim(($branch->address ?? '').' [audit-test]')]);

        $newAuditCount = AuditLog::count();
        $this->info('AuditLog count after update: '.$newAuditCount.' (added: '.($newAuditCount - $initialAuditCount).')');

        $latestAudit = AuditLog::latest()->first();
        if ($latestAudit) {
            $this->line('✓ Latest Audit Log:');
            $this->line("  Event: {$latestAudit->event}");
            $this->line("  Type: {$latestAudit->auditable_type}");
            $this->line("  User: {$latestAudit->user_id}");
        } else {
            $this->error('✗ No audit logs found - listener may not be working!');
        }

        $branch->update(['address' => $originalAddress]);

        $this->info('✓ Audit logging test complete');
    }
}
