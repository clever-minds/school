<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ConsentFormPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates consent form permissions across all tenant databases and assigns them to School Admin.
     * School Admin can then assign or unassign these permissions to Principal, Teachers, or other staff roles.
     *
     * @return void
     */
    public function run(): void
    {
        $permissions = [
            'consent-form-list',
            'consent-form-create',
            'consent-form-edit',
            'consent-form-delete',
        ];

        $this->command->info("🚀 Starting to sync consent form permissions...");

        // 1. Sync on Main Database (mysql)
        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->command->info("✅ Consent form permissions created in main database.");

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
            $this->command->info("✅ Consent form permissions assigned to Super Admin in main DB.");
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
            $this->command->info("✅ Consent form permissions assigned to School Admin in main DB.");
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

                // 2c. Assign exclusively to School Admin role in tenant database
                $adminRoleId = DB::connection('school')->table('roles')->where('name', 'School Admin')->value('id');
                if ($adminRoleId && !empty($tenantPermMap)) {
                    $rows = [];
                    foreach ($tenantPermMap as $permId) {
                        $rows[] = ['permission_id' => $permId, 'role_id' => $adminRoleId];
                    }
                    DB::connection('school')->table('role_has_permissions')->insertOrIgnore($rows);
                    $this->command->info("   ✅ Consent form permissions assigned to 'School Admin' in {$schoolName}");
                }

                // 2d. Remove consent form permissions from all OTHER roles (e.g. Principal, Teacher, Staff)
                // so that staff does NOT see it until School Admin explicitly assigns it via Roles management
                if (!empty($tenantPermMap)) {
                    $otherRoleQuery = DB::connection('school')->table('roles');
                    if ($adminRoleId) {
                        $otherRoleQuery->where('id', '!=', $adminRoleId);
                    }
                    $otherRoleIds = $otherRoleQuery->pluck('id')->toArray();

                    if (!empty($otherRoleIds)) {
                        DB::connection('school')->table('role_has_permissions')
                            ->whereIn('permission_id', array_values($tenantPermMap))
                            ->whereIn('role_id', $otherRoleIds)
                            ->delete();
                    }

                    // Also remove from model_has_permissions if directly assigned to any user
                    DB::connection('school')->table('model_has_permissions')
                        ->whereIn('permission_id', array_values($tenantPermMap))
                        ->delete();

                    $this->command->info("   🔒 Removed consent permissions from staff/other roles in {$schoolName} (School Admin can now assign manually)");
                }

                // 2d. Backfill existing students with parent creation date if applicable
                try {
                    DB::connection('school')->statement("
                        UPDATE students
                        JOIN users ON students.guardian_id = users.id
                        SET students.consent_form_date = users.created_at
                        WHERE students.consent_form_date IS NULL 
                        OR TIME(students.consent_form_date) = '00:00:00'
                    ");
                } catch (Throwable $e) {
                    // Ignore if students table or columns are not in this tenant DB
                }

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            } catch (Throwable $e) {
                $this->command->error("   ❌ Failed for {$schoolName}: " . $e->getMessage());
            }
        }

        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("\n🎉 Consent form permissions synced successfully across all databases!");
    }
}
