<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_returns_201_and_respects_unique_title_per_due_date(): void
    {
        $payload = [
            'title' => 'Unique task',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => 'high',
        ];

        $this->postJson('/api/tasks', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Unique task')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'pending');

        $this->postJson('/api/tasks', $payload)
            ->assertStatus(422);
    }

    public function test_list_tasks_sorts_by_priority_then_due_date(): void
    {
        $d1 = now()->addDays(2)->toDateString();
        $d2 = now()->addDays(3)->toDateString();

        Task::create([
            'title' => 'Low first due',
            'due_date' => $d1,
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::Pending,
        ]);
        Task::create([
            'title' => 'High later due',
            'due_date' => $d2,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Pending,
        ]);

        $ids = $this->getJson('/api/tasks')
            ->assertOk()
            ->json('data.*.id');

        $this->assertSame([2, 1], $ids);
    }

    public function test_status_can_only_progress_forward(): void
    {
        $task = Task::create([
            'title' => 'Flow',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Pending,
        ]);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertStatus(422);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress'])
            ->assertOk();

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertOk();
    }

    public function test_only_done_tasks_can_be_deleted(): void
    {
        $open = Task::create([
            'title' => 'Open',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::Pending,
        ]);

        $this->deleteJson("/api/tasks/{$open->id}")
            ->assertForbidden();

        $done = Task::create([
            'title' => 'Closed',
            'due_date' => now()->addDay()->toDateString(),
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::Done,
        ]);

        $this->deleteJson("/api/tasks/{$done->id}")
            ->assertNoContent();
    }

    public function test_daily_report_groups_by_priority_and_status(): void
    {
        $day = '2026-04-10';

        Task::create([
            'title' => 'A',
            'due_date' => $day,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Pending,
        ]);
        Task::create([
            'title' => 'B',
            'due_date' => $day,
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Pending,
        ]);
        Task::create([
            'title' => 'C',
            'due_date' => $day,
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Done,
        ]);

        $this->getJson('/api/tasks/report?date='.$day)
            ->assertOk()
            ->assertJsonPath('summary.high.pending', 2)
            ->assertJsonPath('summary.medium.done', 1)
            ->assertJsonPath('summary.low.pending', 0);
    }
}
