<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeacherInterviewPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'teacher-interview-manage',
            'teacher-interview-list',
            'teacher-interview-create',
            'teacher-interview-edit',
            'teacher-interview-delete',
            'teacher-interview-question-list',
            'teacher-interview-question-create',
            'teacher-interview-question-edit',
            'teacher-interview-question-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Automatically assign to Super Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Revoke from School Admin and Teacher if previously assigned
        $schoolAdmin = Role::where('name', 'School Admin')->first();
        if ($schoolAdmin) {
            $schoolAdmin->revokePermissionTo($permissions);
        }

        $teacher = Role::where('name', 'Teacher')->first();
        if ($teacher) {
            $teacher->revokePermissionTo($permissions);
        }
    }
}
