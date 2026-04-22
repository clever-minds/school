<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class MigrateAllTenants extends Command
{
    protected $signature = 'tenants:migrate';
    protected $description = 'Run migrations on all tenant databases';

    public function handle()
    {
        $tenants = DB::table('schools')->get();

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->name}");

            // ✅ Complete DB configuration for tenant
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $tenant->database_name,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', 'Lcischool@Cm91011'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
            ]);

            // 🔁 Refresh connection
            DB::purge('tenant');
            DB::reconnect('tenant');

            // 🧩 Test connection
            try {
                DB::connection('tenant')->getPdo();
                $this->info("✅ Connected to tenant DB: {$tenant->database_name}");
            } catch (\Exception $e) {
                $this->error("❌ Connection failed for {$tenant->name}: {$e->getMessage()}");
                continue;
            }

            // 🚀 Run single migration file
            try {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_11_10_125851_add_blood_group_to_users_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_01_000000_add_rte_status_to_students_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_01_174831_add_cast_to_students_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_17_140405_add_type_to_assignments_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_18_130133_create_notification_users_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_18_151123_add_user_role_to_notification_users_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_18_153024_add_impersonation_columns_to_users_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_20_102351_add_event_date_to_notifications_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_29_133406_add_extra_fields_to_students_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_29_133613_add_mother_name_to_users_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_30_165457_add_dise_code_to_schools_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2025_12_30_165746_add_reason_for_leaving_to_students_table.php',
                    '--force' => true,
                ]);
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_07_131033_add_pen_no_to_students_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_08_163320_add_transaction_id_to_compulsory_fees_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_08_163320_add_transaction_id_to_optional_fees_table.php',
                    '--force' => true,
                ]);

                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_09_000002_add_bank_name_after_cheque_no_to_optional_fees_table.php',
                    '--force' => true,
                ]);
                 Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_14_000001_add_student_user_id_to_messages_table.php',
                    '--force' => true,
                ]);
                   Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_01_16_104537_add_remark_to_optional_and_compulsory_fees_tables.php',
                    '--force' => true,
                ]);
                  Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_03_16_104926_add_type_and_class_ids_to_holidays_table.php',
                    '--force' => true,
                ]);
                  Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/schools/2026_04_10_093353_create_student_pickup_requests_table.php',
                    '--force' => true,
                ]);
                  Artisan::call('migrate', [
                      '--database' => 'tenant',
                      '--path' => 'database/migrations/schools/2026_04_10_101311_create_teacher_onboarding_tables.php',
                      '--force' => true,
                  ]);
                  Artisan::call('migrate', [
                      '--database' => 'tenant',
                      '--path' => 'database/migrations/schools/2026_04_16_105510_create_staff_attendances_table.php',
                      '--force' => true,
                  ]);
                  Artisan::call('migrate', [
                      '--database' => 'tenant',
                      '--path' => 'database/migrations/schools/2026_04_17_161053_create_school_policies_table.php',
                      '--force' => true,
                  ]);
                  Artisan::call('migrate', [
                      '--database' => 'tenant',
                      '--path' => 'database/migrations/schools/2026_04_19_080000_add_latitude_and_longitude_to_schools_table_tenant.php',
                      '--force' => true,
                  ]);
                $this->info("✅ Migration done for {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("❌ Migration failed for {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info('🎉 All tenant migrations completed successfully!');
    }
}