<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TeacherAttendancePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all schools from the main database
        DB::setDefaultConnection('mysql');
        $schools = School::on('mysql')->get();

        if ($schools->isEmpty()) {
            $this->command->error("No valid schools found.");
            return;
        }

        foreach ($schools as $school) {
            try {
                $this->command->info("Syncing attendance-create for school: {$school->name} (DB: {$school->database_name})");
                
                // Switch connection to the specific school database
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                DB::setDefaultConnection('school');

                // Ensure the permission exists in this tenant database
                $permission = Permission::firstOrCreate(['name' => 'attendance-create', 'guard_name' => 'web']);

                // Get Teacher role for this specific database
                $teacherRole = Role::where('name', 'Teacher')->first();

                if ($teacherRole) {
                    $teacherRole->givePermissionTo($permission);
                } else {
                    $this->command->warn("Teacher role not found in DB: {$school->database_name}");
                }

                // Forget cached permissions for this database
                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            } catch (\Exception $e) {
                $this->command->error("Failed for school {$school->database_name}: " . $e->getMessage());
            }
        }

        // Revert back to the main connection
        DB::setDefaultConnection('mysql');
        
        $this->command->info("All done! attendance-create permission assigned to Teacher roles across all school databases.");
    }
}
