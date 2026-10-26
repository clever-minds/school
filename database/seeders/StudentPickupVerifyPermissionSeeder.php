<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class StudentPickupVerifyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates pickup verify permissions and assigns them to Super Admin, School Admin, Teacher and Principal.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'student-pickup-list',
            'student-pickup-create',
            'student-pickup-edit',
            'student-pickup-delete',
            'student-pickup-verify',
            'student-pickup-verify-otp',
        ];

        $verifyPermissions = [
            'student-pickup-list',
            'student-pickup-verify',
            'student-pickup-verify-otp',
        ];

        $this->command->info("🚀 Starting to sync student pickup permissions...");

        // 1. Sync on Main Database (mysql)
        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->command->info("✅ Permissions created in main database.");

        // Assign to Super Admin in main database
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
            $this->command->info("✅ Permissions assigned to Super Admin in main DB.");
        }

        $mainSchoolAdmins = Role::withoutGlobalScope('school')->where('name', 'School Admin')->get();
        foreach ($mainSchoolAdmins as $adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->command->info("✅ Permissions assigned to School Admin (ID: {$adminRole->id}) in main DB.");
        }

        // 2. Sync to all Tenant Databases
        $schools = DB::connection('mysql')->table('schools')->get();

        foreach ($schools as $school) {
            $schoolName = $school->name ?? "School #{$school->id}";
            $tenantDbName = $school->database_name ?? $school->database ?? $school->db_name ?? null;

            if (empty($tenantDbName)) {
                continue;
            }

            try {
                $this->command->info("\n🏫 Processing Tenant: {$schoolName} ({$tenantDbName})");

                config(['database.connections.school.database' => $tenantDbName]);
                DB::purge('school');
                DB::reconnect('school');

                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                foreach ($permissions as $name) {
                    Permission::on('school')->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }

                $adminRole = Role::on('school')->where('name', 'School Admin')->first();
                if ($adminRole) {
                    $adminRole->givePermissionTo($permissions);
                    $this->command->info("   ✅ Permissions assigned to 'School Admin' in {$schoolName}");
                }

                $teacherRole = Role::on('school')->where('name', 'Teacher')->first();
                if ($teacherRole) {
                    $teacherRole->givePermissionTo($verifyPermissions);
                    $this->command->info("   ✅ Permissions assigned to 'Teacher' in {$schoolName}");
                }

                $principalRole = Role::on('school')->where('name', 'Principal')->first();
                if ($principalRole) {
                    $principalRole->givePermissionTo($verifyPermissions);
                    $this->command->info("   ✅ Permissions assigned to 'Principal' in {$schoolName}");
                }

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            } catch (Throwable $e) {
                $this->command->error("   ❌ Failed for {$schoolName}: " . $e->getMessage());
            }
        }

        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("\n🎉 Student pickup permissions synced successfully across all databases!");
    }
}
