<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignStaffAttendancePermission extends Command
{
    protected $signature = 'tenants:assign-attendance-permission';
    protected $description = 'Assign staff-attendance-list permission to ALL roles in ALL tenant databases';

    public function handle()
    {
        $permissionName = 'staff-attendance-list';
        $this->info("🚀 Starting to assign '{$permissionName}' to ALL roles in ALL tenants...");

        // Get all tenants from main DB
        $tenants = DB::connection('mysql')->table('schools')->get();

        if ($tenants->isEmpty()) {
            $this->error('❌ No tenants found in schools table.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("\n🎓 Processing school: {$tenant->name}");

            try {
                $tenantDbName = $tenant->database_name;

                if (empty($tenantDbName)) {
                    $this->error("❌ No database name found for school {$tenant->name}");
                    continue;
                }

                // Switch DB to tenant
                config(['database.connections.school.database' => $tenantDbName]);
                DB::purge('school');
                DB::reconnect('school');

                // Clear old permission cache
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                // Ensure permission exists in tenant DB
                $permission = Permission::on('school')->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                // Get ALL roles in this tenant
                $roles = Role::on('school')->get();

                foreach ($roles as $role) {
                    // Skip Student and Guardian roles if you want, but user said "sabhi role"
                    // However, it's safer to give it to everyone as requested.
                    try {
                        $role->givePermissionTo($permission);
                        $this->info("✅ Assigned to role: '{$role->name}'");
                    } catch (\Exception $e) {
                        $this->error("⚠️ Failed assigning to '{$role->name}': " . $e->getMessage());
                    }
                }

                // Final cache cleanup per tenant
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->error("❌ Error for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 Task completed successfully!");
    }
}
