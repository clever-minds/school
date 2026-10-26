<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\Holiday;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SyncHolidaysToAllSchools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holiday:sync-all {source_school_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync holidays from a source school to all tenant schools';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting holiday synchronization across schools...");

        $sourceSchoolId = $this->argument('source_school_id');

        // Find source school
        if ($sourceSchoolId) {
            $sourceSchool = School::on('mysql')->find($sourceSchoolId);
            if (!$sourceSchool) {
                $this->error("Source school with ID {$sourceSchoolId} not found.");
                return 1;
            }
        } else {
            // Find first school that has holidays
            $schools = School::on('mysql')->get();
            $sourceSchool = null;
            $sourceHolidays = null;

            foreach ($schools as $s) {
                if (!empty($s->database_name)) {
                    Config::set('database.connections.school.database', $s->database_name);
                    DB::purge('school');
                    DB::connection('school')->reconnect();
                    DB::setDefaultConnection('school');
                }

                $hols = Holiday::where('school_id', $s->id)->get();
                if ($hols->isNotEmpty()) {
                    $sourceSchool = $s;
                    $sourceHolidays = $hols;
                    break;
                }
            }

            if (!$sourceSchool) {
                $this->error("No school with existing holidays found to use as source.");
                return 1;
            }
        }

        // Connect to source school database and fetch holidays
        if (!empty($sourceSchool->database_name)) {
            Config::set('database.connections.school.database', $sourceSchool->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            DB::setDefaultConnection('school');
        }

        $holidays = Holiday::where('school_id', $sourceSchool->id)->get();
        if ($holidays->isEmpty()) {
            $this->error("Source school '{$sourceSchool->name}' has no holidays to sync.");
            return 1;
        }

        $this->info("Found {$holidays->count()} holidays in source school: {$sourceSchool->name} (ID: {$sourceSchool->id})");

        // Target schools
        $targetSchools = School::on('mysql')->where('id', '!=', $sourceSchool->id)->get();
        $this->info("Syncing to {$targetSchools->count()} target schools...");

        $syncedCount = 0;
        foreach ($targetSchools as $target) {
            try {
                $this->line("-> Syncing to school: {$target->name} (ID: {$target->id})");

                if (!empty($target->database_name)) {
                    Config::set('database.connections.school.database', $target->database_name);
                    DB::purge('school');
                    DB::connection('school')->reconnect();
                    DB::setDefaultConnection('school');
                }

                foreach ($holidays as $h) {
                    Holiday::updateOrCreate(
                        [
                            'type'      => $h->type,
                            'date'      => $h->getRawOriginal('date'),
                            'title'     => $h->title,
                            'school_id' => $target->id,
                        ],
                        [
                            'end_date'    => $h->getRawOriginal('end_date'),
                            'description' => $h->description,
                        ]
                    );
                }
                $syncedCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to sync to school {$target->name}: " . $e->getMessage());
                Log::error("Command holiday:sync-all error on school {$target->id}: " . $e->getMessage());
            }
        }

        $this->info("Synchronization completed! Successfully synced to {$syncedCount} schools.");
        return 0;
    }
}
