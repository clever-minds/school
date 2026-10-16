<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PerformanceIncentivePayrollSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds 'Performance Incentives' (type: allowance, amount: 0) to payroll settings for all schools.
     *
     * @return void
     */
    public function run()
    {
        $settingName = 'Performance Incentives';
        $settingType = 'allowance';
        $defaultAmount = 0;

        $this->command->info("🚀 Starting to add '{$settingName}' to all schools payroll settings...");

        // Ensure we check the main database connection
        DB::setDefaultConnection('mysql');

        // Check if schools table exists in main DB
        if (!Schema::connection('mysql')->hasTable('schools')) {
            $this->command->error("❌ 'schools' table not found on mysql connection.");
            $this->seedSingleDatabaseFallback($settingName, $settingType, $defaultAmount);
            return;
        }

        $schools = DB::connection('mysql')->table('schools')->get();

        if ($schools->isEmpty()) {
            $this->command->warn("⚠️ No schools found in 'schools' table. Attempting fallback seeding on current database...");
            $this->seedSingleDatabaseFallback($settingName, $settingType, $defaultAmount);
            return;
        }

        foreach ($schools as $school) {
            $schoolName = $school->name ?? "School #{$school->id}";
            $this->command->info("\n🏫 Processing School: {$schoolName} (ID: {$school->id})");

            // 1. Seed into Main Database (mysql) if payroll_settings table exists
            try {
                if (Schema::connection('mysql')->hasTable('payroll_settings')) {
                    $this->ensurePayrollSetting(
                        'mysql',
                        $school->id,
                        $school->admin_id ?? null,
                        $settingName,
                        $settingType,
                        $defaultAmount
                    );
                }
            } catch (Throwable $e) {
                $this->command->error("⚠️ Error seeding in main DB for {$schoolName}: " . $e->getMessage());
            }

            // 2. Seed into Tenant Database if separate database_name is configured
            $tenantDbName = $school->database_name ?? $school->database ?? $school->db_name ?? null;

            if (!empty($tenantDbName)) {
                try {
                    $this->command->line("   Switching to tenant database: {$tenantDbName}");

                    Config::set('database.connections.school.database', $tenantDbName);
                    DB::purge('school');
                    DB::connection('school')->reconnect();

                    if (Schema::connection('school')->hasTable('payroll_settings')) {
                        $this->ensurePayrollSetting(
                            'school',
                            $school->id,
                            $school->admin_id ?? null,
                            $settingName,
                            $settingType,
                            $defaultAmount
                        );
                    } else {
                        $this->command->line("   ℹ️ 'payroll_settings' table does not exist in tenant DB {$tenantDbName}");
                    }
                } catch (Throwable $e) {
                    $this->command->error("⚠️ Error seeding tenant DB ({$tenantDbName}) for {$schoolName}: " . $e->getMessage());
                }
            }
        }

        // Revert default connection back to mysql
        DB::setDefaultConnection('mysql');

        $this->command->info("\n🎉 '{$settingName}' seeded successfully across all schools!");
    }

    /**
     * Ensure the payroll setting exists in the specified database connection.
     */
    private function ensurePayrollSetting(string $connection, int $schoolId, ?int $adminId, string $name, string $type, float $amount): void
    {
        $hasSchoolIdCol = Schema::connection($connection)->hasColumn('payroll_settings', 'school_id');
        $hasSoftDeletes = Schema::connection($connection)->hasColumn('payroll_settings', 'deleted_at');

        $query = DB::connection($connection)->table('payroll_settings')->where('name', $name);
        if ($hasSchoolIdCol) {
            $query->where('school_id', $schoolId);
        }

        $existing = $query->first();

        $settingId = null;

        if ($existing) {
            $settingId = $existing->id;
            $updateData = [
                'type' => $type,
                'updated_at' => now(),
            ];

            // If amount is null, set to 0
            if ($existing->amount === null) {
                $updateData['amount'] = $amount;
            }

            // If soft-deleted, restore it
            if ($hasSoftDeletes && !empty($existing->deleted_at)) {
                $updateData['deleted_at'] = null;
                $this->command->info("   ♻️ Restored existing '{$name}' in connection [{$connection}].");
            } else {
                $this->command->line("   ✓ '{$name}' already exists in connection [{$connection}]. Updated type to '{$type}'.");
            }

            DB::connection($connection)->table('payroll_settings')->where('id', $existing->id)->update($updateData);
        } else {
            $insertData = [
                'name' => $name,
                'amount' => $amount,
                'percentage' => null,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasSchoolIdCol) {
                $insertData['school_id'] = $schoolId;
            }

            $settingId = DB::connection($connection)->table('payroll_settings')->insertGetId($insertData);
            $this->command->info("   ✅ Created '{$name}' in connection [{$connection}] (ID: {$settingId}).");
        }

        // Track session year if session_years_trackings exists
        if ($settingId) {
            $this->ensureSessionYearTracking($connection, $settingId, $schoolId, $adminId);
        }
    }

    /**
     * Ensure session year tracking record exists for the payroll setting.
     */
    private function ensureSessionYearTracking(string $connection, int $settingId, int $schoolId, ?int $adminId): void
    {
        if (!Schema::connection($connection)->hasTable('session_years_trackings') ||
            !Schema::connection($connection)->hasTable('session_years')) {
            return;
        }

        try {
            $sessionYearQuery = DB::connection($connection)->table('session_years');
            if (Schema::connection($connection)->hasColumn('session_years', 'school_id')) {
                $sessionYearQuery->where('school_id', $schoolId);
            }

            $sessionYear = (clone $sessionYearQuery)->where('default', 1)->first()
                ?? $sessionYearQuery->first();

            if (!$sessionYear) {
                return;
            }

            $trackingQuery = DB::connection($connection)->table('session_years_trackings')
                ->where('modal_type', 'App\Models\PayrollSetting')
                ->where('modal_id', $settingId);

            if (Schema::connection($connection)->hasColumn('session_years_trackings', 'school_id')) {
                $trackingQuery->where('school_id', $schoolId);
            }

            $tracking = $trackingQuery->first();

            if (!$tracking) {
                $trackingData = [
                    'modal_type' => 'App\Models\PayrollSetting',
                    'modal_id' => $settingId,
                    'user_id' => $adminId,
                    'session_year_id' => $sessionYear->id,
                    'semester_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::connection($connection)->hasColumn('session_years_trackings', 'school_id')) {
                    $trackingData['school_id'] = $schoolId;
                }

                DB::connection($connection)->table('session_years_trackings')->insert($trackingData);
                $this->command->line("   📌 Attached session year tracking (Session Year ID: {$sessionYear->id}).");
            }
        } catch (Throwable $e) {
            // Non-critical, ignore tracking failure
        }
    }

    /**
     * Fallback for single-database environments without multi-tenant records.
     */
    private function seedSingleDatabaseFallback(string $name, string $type, float $amount): void
    {
        try {
            $connection = DB::getDefaultConnection();
            if (Schema::connection($connection)->hasTable('payroll_settings')) {
                $this->ensurePayrollSetting($connection, 1, null, $name, $type, $amount);
                $this->command->info("✅ Fallback seeded in default connection [{$connection}].");
            }
        } catch (Throwable $e) {
            $this->command->error("Fallback failed: " . $e->getMessage());
        }
    }
}
