<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class StaffPerformancePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates staff performance permissions and assigns them to School Admin on all tenant databases.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'staff-performance-list',
            'staff-performance-create',
            'staff-performance-edit',
            'staff-performance-delete',
        ];

        $this->command->info("🚀 Starting to sync staff performance permissions...");

        // 1. Sync on Main Database (mysql)
        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $this->command->info("✅ Permissions created in main database.");

        // Assign to Super Admin and School Admin in main database
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

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            } catch (Throwable $e) {
                $this->command->error("   ❌ Failed for {$schoolName}: " . $e->getMessage());
            }
        }

        DB::setDefaultConnection('mysql');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info("\n🎉 Staff performance permissions synced successfully across all databases!");
    }
}
