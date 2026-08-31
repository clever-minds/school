<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    // Temporary storage for old data (not saved to DB)
    protected $oldAttributesData = [];

    public static function bootLogsActivity()
    {
        static::updating(function ($model) {
            $model->oldAttributesData = $model->getOriginal();
        });

        static::updated(function ($model) {
            $old = $model->oldAttributesData ?? [];
            $new = $model->getChanges();

            $changes = [
                'old' => $old,
                'new' => $new,
            ];

            self::storeActivity($model, 'Update', $changes);
        });

        static::created(function ($model) {
            $changes = [
                'old' => [],
                'new' => $model->getAttributes(),
            ];

            self::storeActivity($model, 'Create', $changes);
        });
    }

    public function newEloquentBuilder($query)
    {
        return new \App\Builders\LoggingEloquentBuilder($query);
    }

    protected static function storeActivity($model, $action, $changes = null)
    {
        if (is_array($changes)) {
            $changes['request_url'] = Request::fullUrl();
            $changesJson = json_encode($changes, JSON_PRETTY_PRINT);
        } else {
            $changesJson = json_encode(['request_url' => Request::fullUrl()]);
        }

        ActivityLog::create([
            'user_id'    => Auth::id(),
            'model_name' => class_basename($model),
            'action'     => $action,
            'record_id'  => $model->id ?? null,
            'changes'    => $changesJson,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
