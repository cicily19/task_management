<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'done'])],
        ]);

        $query = Task::query()
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
            ->orderBy('due_date');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create([
            'title' => $request->validated('title'),
            'due_date' => $request->validated('due_date'),
            'priority' => $request->validated('priority'),
            'status' => TaskStatus::Pending,
        ]);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $new = TaskStatus::from($request->validated('status'));

        if (! $this->isValidStatusProgression($task->status, $new)) {
            throw ValidationException::withMessages([
                'status' => ['Status can only move forward: pending → in_progress → done. Skipping or reverting is not allowed.'],
            ]);
        }

        $task->update(['status' => $new]);

        return (new TaskResource($task->fresh()))->response();
    }

    public function destroy(Task $task): JsonResponse|Response
    {
        if ($task->status !== TaskStatus::Done) {
            return response()->json([
                'message' => 'Only tasks with status done can be deleted.',
            ], Response::HTTP_FORBIDDEN);
        }

        $task->delete();

        return response()->noContent();
    }

    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'];

        $priorities = ['high', 'medium', 'low'];
        $statuses = ['pending', 'in_progress', 'done'];

        $summary = [];
        foreach ($priorities as $priority) {
            $summary[$priority] = array_fill_keys($statuses, 0);
        }

        $rows = Task::query()
            ->whereDate('due_date', $date)
            ->selectRaw('priority, status, COUNT(*) as c')
            ->groupBy('priority', 'status')
            ->get();

        foreach ($rows as $row) {
            $summary[$row->priority][$row->status] = (int) $row->c;
        }

        return response()->json([
            'date' => $date,
            'summary' => $summary,
        ]);
    }

    private function isValidStatusProgression(TaskStatus $current, TaskStatus $next): bool
    {
        return match ($current) {
            TaskStatus::Pending => $next === TaskStatus::InProgress,
            TaskStatus::InProgress => $next === TaskStatus::Done,
            TaskStatus::Done => false,
        };
    }
}
