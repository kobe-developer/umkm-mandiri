<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/** Mencatat siapa mengubah apa (log aktivitas owner). */
class ActivityLogger
{
    private const HIDDEN_KEYS = ['password', 'pin_hash', 'remember_token', 'public_token'];

    public function log(string $action, Model|string $subject, ?array $before = null, ?array $after = null, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject instanceof Model ? class_basename($subject) : $subject,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'description' => $description,
            'before' => $before ? $this->clean($before) : null,
            'after' => $after ? $this->clean($after) : null,
            'ip_address' => Request::ip(),
        ]);
    }

    /** Catat hanya atribut yang berubah. */
    public function logChanges(string $action, Model $model, array $original, ?string $description = null): ?ActivityLog
    {
        $changed = array_keys($model->getChanges());
        $changed = array_values(array_diff($changed, ['updated_at']));
        if ($changed === []) {
            return null;
        }

        return $this->log(
            $action,
            $model,
            array_intersect_key($original, array_flip($changed)),
            array_intersect_key($model->getAttributes(), array_flip($changed)),
            $description,
        );
    }

    private function clean(array $data): array
    {
        return array_diff_key($data, array_flip(self::HIDDEN_KEYS));
    }
}
