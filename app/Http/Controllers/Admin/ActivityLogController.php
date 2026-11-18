<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', 'string', 'max:50'],
        ]);

        return Inertia::render('Admin/ActivityLogs', [
            'logs' => fn () => ActivityLog::with('user:id,name')
                ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
                ->when($filters['subject_type'] ?? null, fn ($q, $v) => $q->where('subject_type', $v))
                ->latest('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'users' => fn () => User::orderBy('name')->get(['id', 'name'])->map->only(['id', 'name']),
            'subjectTypes' => fn () => ActivityLog::distinct()->orderBy('subject_type')->pluck('subject_type'),
            'filters' => $filters,
        ]);
    }
}
