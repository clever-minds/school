<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
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
            'student-pickup-verify-otp',
        ];

        $verifyPermissions = [
            'student-pickup-list',
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

        $mainPermMap = DB::connection('mysql')->table('permissions')
            ->whereIn('name', $permissions)
            ->pluck('id', 'name')
            ->toArray();

        // Assign to Super Admin in main database
        $superAdminId = DB::connection('mysql')->table('roles')->where('name', 'Super Admin')->value('id');
        if ($superAdminId && !empty($mainPermMap)) {
            $rows = [];
            foreach ($mainPermMap as $permId) {
                $rows[] = ['permission_id' => $permId, 'role_id' => $superAdminId];
            }
            DB::connection('mysql')->table('role_has_permissions')->insertOrIgnore($rows);
            $this->command->info("✅ Permissions assigned to Super Admin in main DB.");
        }

        // Assign to School Admin in main database
        $mainSchoolAdminIds = DB::connection('mysql')->table('roles')->where('name', 'School Admin')->pluck('id')->toArray();
        if (!empty($mainSchoolAdminIds) && !empty($mainPermMap)) {
            $rows = [];
            foreach ($mainSchoolAdminIds as $adminId) {
                foreach ($mainPermMap as $permId) {
                    $rows[] = ['permission_id' => $permId, 'role_id' => $adminId];
                }
            }
            DB::connection('mysql')->table('role_has_permissions')->insertOrIgnore($rows);
            $this->command->info("✅ Permissions assigned to School Admin in main DB.");
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
                DB::setDefaultConnection('school');

                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                // 2a. Create permissions in tenant database
                foreach ($permissions as $name) {
                    Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }

                // 2b. Fetch tenant's actual permission IDs to avoid foreign key constraints
                $tenantPermMap = DB::connection('school')->table('permissions')
                    ->whereIn('name', $permissions)
                    ->pluck('id', 'name')
                    ->toArray();

                // 2c. Assign to School Admin
                $adminRoleId = DB::connection('school')->table('roles')->where('name', 'School Admin')->value('id');
                if ($adminRoleId && !empty($tenantPermMap)) {
                    $rows = [];
                    foreach ($tenantPermMap as $permId) {
                        $rows[] = ['permission_id' => $permId, 'role_id' => $adminRoleId];
                    }
                    DB::connection('school')->table('role_has_permissions')->insertOrIgnore($rows);
                    $this->command->info("   ✅ Permissions assigned to 'School Admin' in {$schoolName}");
                }

                // 2d. Assign to Teacher
                $teacherRoleId = DB::connection('school')->table('roles')->where('name', 'Teacher')->value('id');
                if ($teacherRoleId && !empty($tenantPermMap)) {
                    $rows = [];
                    foreach ($verifyPermissions as $pName) {
                        if (isset($tenantPermMap[$pName])) {
                            $rows[] = ['permission_id' => $tenantPermMap[$pName], 'role_id' => $teacherRoleId];
                        }
                    }
                    if (!empty($rows)) {
                        DB::connection('school')->table('role_has_permissions')->insertOrIgnore($rows);
                    }
                    $this->command->info("   ✅ Permissions assigned to 'Teacher' in {$schoolName}");
                }

                // 2e. Assign to Principal
                $principalRoleId = DB::connection('school')->table('roles')->where('name', 'Principal')->value('id');
                if ($principalRoleId && !empty($tenantPermMap)) {
                    $rows = [];
                    foreach ($verifyPermissions as $pName) {
                        if (isset($tenantPermMap[$pName])) {
                            $rows[] = ['permission_id' => $tenantPermMap[$pName], 'role_id' => $principalRoleId];
                        }
                    }
                    if (!empty($rows)) {
                        DB::connection('school')->table('role_has_permissions')->insertOrIgnore($rows);
                    }
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
