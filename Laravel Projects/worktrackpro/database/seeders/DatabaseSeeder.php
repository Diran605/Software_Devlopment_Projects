<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organisation;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Roles and Permissions
        $this->call(RoleAndPermissionSeeder::class);

        // 2. Default Organisation
        $org = Organisation::create([
            'name' => 'WorkTrack Demo Org',
            'slug' => 'worktrack-demo',
            'is_active' => true,
        ]);

        // 3. Departments
        $engineering = Department::create([
            'organisation_id' => $org->id,
            'name' => 'Engineering',
            'description' => 'Software development team',
        ]);

        $design = Department::create([
            'organisation_id' => $org->id,
            'name' => 'Design',
            'description' => 'Product design team',
        ]);

        // 4. Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@worktrackpro.test',
            'password' => Hash::make('password'),
            'organisation_id' => $org->id,
            'department_id' => null, // Cross-department
            'is_active' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        $superAdmin->assignRole('super_admin');

        // 5. Admins
        $engAdmin = User::create([
            'name' => 'Eng Manager',
            'email' => 'admin.eng@worktrackpro.test',
            'password' => Hash::make('password'),
            'organisation_id' => $org->id,
            'department_id' => $engineering->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $engAdmin->assignRole('admin');
        
        $engineering->update(['created_by' => $superAdmin->id]);
        $design->update(['created_by' => $superAdmin->id]);

        $designAdmin = User::create([
            'name' => 'Design Manager',
            'email' => 'admin.design@worktrackpro.test',
            'password' => Hash::make('password'),
            'organisation_id' => $org->id,
            'department_id' => $design->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $designAdmin->assignRole('admin');

        // 6. Workers
        $workers = [
            ['name' => 'Alice Dev', 'email' => 'worker1@worktrackpro.test', 'department_id' => $engineering->id],
            ['name' => 'Bob Dev', 'email' => 'worker2@worktrackpro.test', 'department_id' => $engineering->id],
            ['name' => 'Charlie Dev', 'email' => 'worker3@worktrackpro.test', 'department_id' => $engineering->id],
            ['name' => 'Diana UI', 'email' => 'worker4@worktrackpro.test', 'department_id' => $design->id],
            ['name' => 'Eve UX', 'email' => 'worker5@worktrackpro.test', 'department_id' => $design->id],
        ];

        foreach ($workers as $workerData) {
            $user = User::create([
                'name' => $workerData['name'],
                'email' => $workerData['email'],
                'password' => Hash::make('password'),
                'organisation_id' => $org->id,
                'department_id' => $workerData['department_id'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('worker');
        }
    }
}
