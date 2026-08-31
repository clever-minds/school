<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::orderBy('id', 'desc')->first();
if ($user) {
    echo "Query Builder Deleting user ID: " . $user->id . "\n";
    \App\Models\User::where('id', $user->id)->delete();
    
    $logs = \App\Models\ActivityLog::where('record_id', $user->id)->orderBy('id', 'desc')->get();
    foreach ($logs as $log) {
        echo "Log: " . $log->action . " for model " . $log->model_name . "\n";
    }
}
echo "DONE\n";
