<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\ActivityLog;

class LoggingEloquentBuilder extends Builder
{
    public function delete()
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_class($this->getModel())))) {
            return parent::delete();
        }

        $models = $this->get();
        $result = parent::delete();

        foreach ($models as $model) {
            $changes = [
                'old' => $model->getOriginal(),
                'new' => [],
            ];
            self::logActivity($model, 'Delete', $changes);
        }

        return $result;
    }

    public function forceDelete()
    {
        $models = $this->get();
        
        $result = parent::forceDelete();

        foreach ($models as $model) {
            $changes = [
                'old' => $model->getOriginal(),
                'new' => [],
            ];
            self::logActivity($model, 'Force Delete', $changes);
        }

        return $result;
    }

    public function update(array $values)
    {
        $isSoftDelete = array_key_exists('deleted_at', $values) && $values['deleted_at'] !== null;
        $isRestore = array_key_exists('deleted_at', $values) && $values['deleted_at'] === null;
        
        if (! $isSoftDelete && ! $isRestore) {
            return parent::update($values);
        }

        $models = $this->get();
        $result = parent::update($values);

        foreach ($models as $model) {
            $originalDeletedAt = $model->getRawOriginal('deleted_at');
            
            if ($isSoftDelete && $originalDeletedAt === null) {
                $action = 'Delete';
            } elseif ($isRestore && $originalDeletedAt !== null) {
                $action = 'Restore';
            } else {
                continue;
            }

            $changes = [
                'old' => $model->getOriginal(),
                'new' => $values,
            ];
            self::logActivity($model, $action, $changes);
        }
        
        return $result;
    }

    protected static function logActivity($model, $action, $changes)
    {
        try {
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
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
